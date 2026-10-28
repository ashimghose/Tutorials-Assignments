<?php

use App\Actions\Orders\ChangeOrderStatus;
use App\Models\AcademicOrder;
use App\Models\AcademicStudent;
use App\Models\OrderFile;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\NewAcademicOrder;
use App\Notifications\OrderReceived;
use App\Notifications\OrderStatusChanged;
use Database\Seeders\AcademicCatalogueSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;

function pdfUpload(string $name = 'draft.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
}

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    config(['academic.gst_percent' => 18]);
    $this->seed(AcademicCatalogueSeeder::class);
    $this->student = AcademicStudent::factory()->create(['university' => 'Jadavpur University', 'course' => 'MA', 'level' => 'masters']);
    $this->user = $this->student->user;
    $this->service = Service::factory()->create();
    $this->copy = ServiceAddon::factory()->create();
    $this->courier = ServiceAddon::factory()->courier()->create();
    $this->service->addons()->attach([$this->copy->id, $this->courier->id]);
});

function orderForm($component, array $values = [])
{
    $values = [
        'service_id' => (string) test()->service->id,
        'level' => 'masters',
        'topic' => 'Partition in Bengali fiction',
        'deadline' => now()->addWeeks(3)->toDateString(),
        'pages' => '80',
        ...$values,
    ];
    foreach ($values as $field => $value) {
        $component->set($field, $value);
    }

    return $component;
}

test('ACA-4 a student places an order; the server prices it and it waits for payment', function () {
    $admin = User::factory()->admin()->create();

    $page = orderForm(Livewire::actingAs($this->user)->test('pages::portal.academic.new-order'))
        ->assertSet('university', 'Jadavpur University')
        ->assertSee('₹9,440')
        ->set('uploads', [pdfUpload()])
        ->call('place')
        ->assertHasNoErrors();

    $order = AcademicOrder::sole();
    $page->assertRedirect(route('academic.orders.show', $order));

    expect($order->total)->toBe(944000)
        ->and($order->gst)->toBe(144000)
        ->and($order->status->is(OrderStatus::initial()))->toBeTrue()
        ->and($order->isPaid())->toBeFalse()
        ->and($order->revisions_allowed)->toBe(2)
        ->and($order->delivery_address)->toBeNull()
        ->and($order->history)->toHaveCount(1);

    $file = $order->files()->sole();
    expect($file->mime)->toBe('application/pdf');
    Storage::disk('local')->assertExists($file->path);

    Notification::assertSentTo($admin, NewAcademicOrder::class);
    Notification::assertSentTo($this->user, OrderReceived::class);
});

test('ACA-4 printing asks for a delivery address, and without it no address is kept', function () {
    $page = orderForm(Livewire::actingAs($this->user)->test('pages::portal.academic.new-order'), ['quantities' => [$this->copy->id => 2]])
        ->set('delivery_address', '')
        ->call('place')
        ->assertHasErrors(['delivery_address', 'delivery_city', 'delivery_pincode']);

    $page->set('delivery_address', '12 College Street')->set('delivery_city', 'Kolkata')->set('delivery_state', 'West Bengal')->set('delivery_pincode', '700073')
        ->call('place')
        ->assertHasNoErrors();

    $order = AcademicOrder::sole();
    expect($order->delivery_city)->toBe('Kolkata')
        ->and($order->total)->toBe((800000 + 2 * 60000) * 118 / 100)
        ->and($order->addons()->sole()->quantity)->toBe(2);
});

test('PAY-1 a price sent from the browser is ignored', function () {
    orderForm(Livewire::actingAs($this->user)->test('pages::portal.academic.new-order'))
        ->set('total', 100)
        ->call('place');

    expect(AcademicOrder::sole()->total)->toBe(944000);
})->throws(PublicPropertyNotFoundException::class);

// Outside tests Livewire reads the type from the file's content, not its name.
test('ACA-4 only PDF or Word files are accepted', function () {
    $fake = UploadedFile::fake()->create('thesis.exe', 20, 'application/x-dosexec');

    orderForm(Livewire::actingAs($this->user)->test('pages::portal.academic.new-order'))
        ->set('uploads', [$fake])
        ->call('place')
        ->assertHasErrors('uploads.0');

    expect(AcademicOrder::count())->toBe(0);
});

test('ACA-5 a service quoted at this level becomes a quote request with no price', function () {
    $quoted = Service::factory()->quoted()->create(['title' => 'Research book editing']);

    orderForm(Livewire::actingAs($this->user)->test('pages::portal.academic.new-order'), ['service_id' => (string) $quoted->id])
        ->assertSee('Ask for a quote')
        ->call('place')
        ->assertHasNoErrors();

    $order = AcademicOrder::sole();
    expect($order->is_quote)->toBeTrue()->and($order->total)->toBeNull();
});

test('only verified academic students can order', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())->get(route('academic.orders.create'))->assertForbidden();
})->with(['parent', 'teacher', 'admin']);

test('ACA-8 students see only their own orders', function () {
    $mine = AcademicOrder::factory()->for($this->student)->create();
    $theirs = AcademicOrder::factory()->create(['topic' => 'Someone else\'s topic']);

    $this->actingAs($this->user)->get(route('academic.orders.show', $mine))->assertOk()->assertSee('Partition in Bengali fiction')->assertSee('₹9,440');
    $this->actingAs($this->user)->get(route('academic.orders.show', $theirs))->assertForbidden();
    Livewire::actingAs($this->user)->test('pages::portal.academic.orders')->assertSee($mine->reference())->assertDontSee($theirs->reference());
});

test('ACA-9 the assigned expert sees the order and others don\'t', function () {
    $expert = Teacher::factory()->create(['offering' => 'academic']);
    $order = AcademicOrder::factory()->paid()->create(['expert_id' => $expert->id]);
    $other = Teacher::factory()->create(['offering' => 'academic']);

    $this->actingAs($expert->user)->get(route('teacher.orders.show', $order))->assertOk();
    Livewire::actingAs($expert->user)->test('pages::portal.teacher.orders')->assertSee($order->reference());
    $this->actingAs($other->user)->get(route('teacher.orders.show', $order))->assertForbidden();
    Livewire::actingAs($other->user)->test('pages::portal.teacher.orders')->assertDontSee($order->reference());
});

test('ACA-9 the expert uploads a draft and moves the status only to statuses experts may set', function () {
    $expert = Teacher::factory()->create(['offering' => 'academic']);
    $order = AcademicOrder::factory()->for($this->student)->paid()->create(['expert_id' => $expert->id]);
    $draftShared = OrderStatus::where('name', 'Draft shared')->sole();
    $shipped = OrderStatus::where('name', 'Shipped')->sole();

    $page = Livewire::actingAs($expert->user)->test('pages::portal.teacher.order', ['order' => $order])
        ->set('kind', 'draft')
        ->set('uploads', [pdfUpload('chapter-1-edited.pdf')])
        ->call('upload')
        ->assertHasNoErrors()
        ->set('status_id', (string) $draftShared->id)
        ->set('note', 'Chapter 1 edited')
        ->call('setStatus')
        ->assertHasNoErrors();

    expect($order->fresh()->status->is($draftShared))->toBeTrue()
        ->and($order->files()->where('kind', 'draft')->count())->toBe(1);
    Notification::assertSentTo($this->user, OrderStatusChanged::class);

    $page->set('status_id', (string) $shipped->id)->call('setStatus')->assertForbidden();
});

test('PAY-2 an unpaid order cannot leave "Awaiting payment" except by being cancelled', function () {
    $order = AcademicOrder::factory()->create();
    $admin = User::factory()->admin()->create();

    expect(fn () => app(ChangeOrderStatus::class)->handle($order, OrderStatus::where('name', 'In progress')->sole(), $admin))
        ->toThrow(ValidationException::class);

    app(ChangeOrderStatus::class)->handle($order, OrderStatus::where('name', 'Cancelled')->sole(), $admin);
    expect($order->fresh()->status->name)->toBe('Cancelled');
});

test('ACA-7 Shipped needs the courier and tracking number', function () {
    $order = AcademicOrder::factory()->paid()->create();
    $shipped = OrderStatus::where('name', 'Shipped')->sole();
    $admin = User::factory()->admin()->create();

    expect(fn () => app(ChangeOrderStatus::class)->handle($order, $shipped, $admin))->toThrow(ValidationException::class);

    app(ChangeOrderStatus::class)->handle($order, $shipped, $admin, null, 'India Post', 'EK123456789IN');
    expect($order->fresh()->tracking_number)->toBe('EK123456789IN');
});

test('ACA-8 the student asks for a revision after a draft, within the free revisions', function () {
    $order = AcademicOrder::factory()->for($this->student)->paid()->create(['revisions_allowed' => 1]);
    $order->files()->make()->forceFill(['kind' => 'draft', 'disk' => 'local', 'path' => 'x.pdf', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 10])->save();

    Livewire::actingAs($this->user)->test('pages::portal.academic.order', ['order' => $order])
        ->set('revision_note', 'Please fix the references in chapter 2.')
        ->call('requestRevision')
        ->assertHasNoErrors();

    expect($order->fresh()->status->is_revision)->toBeTrue()
        ->and($order->fresh()->revisions_used)->toBe(1);

    // No revisions left, and already in revision.
    Livewire::actingAs($this->user)->test('pages::portal.academic.order', ['order' => $order->fresh()])
        ->set('revision_note', 'Again')
        ->call('requestRevision')
        ->assertForbidden();
});

test('order files download through a signed link after an access check', function () {
    $order = AcademicOrder::factory()->for($this->student)->create();
    Storage::disk('local')->put('orders/1/brief.pdf', '%PDF-1.4');
    $file = $order->files()->make()->forceFill(['kind' => 'brief', 'disk' => 'local', 'path' => 'orders/1/brief.pdf', 'original_name' => 'My brief.pdf', 'mime' => 'application/pdf', 'size' => 8]);
    $file->save();
    $url = URL::temporarySignedRoute('orders.files.download', now()->addMinutes(10), $file);

    $this->actingAs($this->user)->get($url)->assertOk()->assertDownload('My brief.pdf');
    $this->actingAs(AcademicStudent::factory()->create()->user)->get($url)->assertForbidden();
    $this->actingAs($this->user)->get(route('orders.files.download', $file))->assertForbidden();
});

test('finished orders\' files are deleted after the retention period', function () {
    config(['academic.retention_days' => 30]);
    $order = AcademicOrder::factory()->paid()->create();
    Storage::disk('local')->put('orders/9/final.pdf', '%PDF-1.4');
    $order->files()->make()->forceFill(['kind' => 'final', 'disk' => 'local', 'path' => 'orders/9/final.pdf', 'original_name' => 'final.pdf', 'mime' => 'application/pdf', 'size' => 8])->save();

    app(ChangeOrderStatus::class)->handle($order, OrderStatus::where('name', 'Complete')->sole(), User::factory()->admin()->create());
    $this->artisan('app:prune-order-files')->assertSuccessful();
    expect(OrderFile::count())->toBe(1);

    $this->travel(31)->days();
    $this->artisan('app:prune-order-files')->assertSuccessful();
    expect(OrderFile::count())->toBe(0);
    Storage::disk('local')->assertMissing('orders/9/final.pdf');
});

test('the order pages are off without the feature flag', function () {
    config(['features.academic_orders' => false]);

    $this->actingAs($this->user)->get('/my-orders/new')->assertNotFound();
});
