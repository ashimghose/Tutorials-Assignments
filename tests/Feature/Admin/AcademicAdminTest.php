<?php

use App\Filament\Resources\AcademicOrders\Pages\ListAcademicOrders;
use App\Filament\Resources\AcademicOrders\Pages\ViewAcademicOrder;
use App\Filament\Resources\OrderStatuses\Pages\ListOrderStatuses;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\SiteSection;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\OrderAssigned;
use App\Notifications\OrderStatusChanged;
use App\Notifications\QuoteReady;
use Database\Seeders\AcademicCatalogueSeeder;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    config(['academic.gst_percent' => 18]);
    $this->seed(AcademicCatalogueSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('ACA-6 the default statuses are seeded in order with their flags', function () {
    expect(OrderStatus::query()->ordered()->pluck('name')->all())->toBe([
        'Awaiting payment', 'Not picked yet', 'Assigned', 'In progress', 'Draft shared', 'Revision requested',
        'Complete', 'Printed', 'Shipped', 'Delivered', 'Cancelled',
    ])
        ->and(OrderStatus::initial()->name)->toBe('Awaiting payment')
        ->and(OrderStatus::afterPayment()->name)->toBe('Not picked yet')
        ->and(OrderStatus::where('needs_tracking', true)->pluck('name')->all())->toBe(['Shipped']);
});

test('ACA-1 ACA-2 the price list and add-ons are copied from the site content once', function () {
    expect(Service::count())->toBe(6)->and(ServiceAddon::count())->toBe(6);

    $synopsis = Service::where('title', 'Synopsis guidance and review')->sole();
    expect($synopsis->price_phd)->toBe(400000)
        ->and($synopsis->type)->toBe('Synopsis')
        ->and($synopsis->addons()->count())->toBe(6)
        ->and(Service::where('title', 'like', 'Research book%')->sole()->price_masters)->toBeNull();

    $synopsis->update(['price_phd' => 450000]);
    $this->seed(AcademicCatalogueSeeder::class);
    expect(Service::count())->toBe(6)->and($synopsis->fresh()->price_phd)->toBe(450000);
});

test('ACA-1 the public academic page shows the admin\'s price list', function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
    Service::where('title', 'Synopsis guidance and review')->update(['price_phd' => 432100]);

    $this->get(route('academic'))->assertSee('₹4,321');
});

test('ACA-1 the admin adds a service with prices in rupees, stored in paise', function () {
    $addon = ServiceAddon::first();

    Livewire::test(CreateService::class)
        ->fillForm([
            'title' => 'Literature review guidance',
            'type' => 'Any document',
            'price_bachelors' => 2000,
            'price_masters' => 3000,
            'price_phd' => null,
            'free_revisions' => 1,
            'addons' => [$addon->id],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = Service::where('title', 'Literature review guidance')->sole();
    expect($service->price_masters)->toBe(300000)->and($service->price_phd)->toBeNull()
        ->and($service->addons()->pluck('service_addons.id')->all())->toBe([$addon->id])
        ->and(AuditLog::where('action', 'service.created')->exists())->toBeTrue();
});

test('ACA-6 a status in use cannot be deleted', function () {
    AcademicOrder::factory()->create();

    expect($this->admin->can('delete', OrderStatus::initial()))->toBeFalse()
        ->and($this->admin->can('delete', OrderStatus::where('name', 'Printed')->sole()))->toBeTrue();
    Livewire::test(ListOrderStatuses::class)->assertCanSeeTableRecords(OrderStatus::all());
});

test('PAY-2 the admin marks an order as paid, which moves it on and is audited', function () {
    $order = AcademicOrder::factory()->create();

    Livewire::test(ViewAcademicOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('markPaid')
        ->assertHasNoActionErrors();

    expect($order->fresh()->isPaid())->toBeTrue()
        ->and($order->fresh()->status->name)->toBe('Not picked yet')
        ->and(AuditLog::where('action', 'order.paid')->where('user_id', $this->admin->id)->exists())->toBeTrue();
    Notification::assertSentTo($order->academicStudent->user, OrderStatusChanged::class);
});

test('ACA-5 the admin sends a quote; GST is added and the student is emailed', function () {
    $order = AcademicOrder::factory()->create(['is_quote' => true, 'subtotal' => null, 'gst' => null, 'total' => null]);

    Livewire::test(ViewAcademicOrder::class, ['record' => $order->getRouteKey()])
        ->assertActionHidden('markPaid')
        ->callAction('quote', data: ['amount' => 20000, 'expires_at' => now()->addWeek()->toDateString(), 'note' => 'For 320 pages'])
        ->assertHasNoActionErrors();

    $order->refresh();
    expect($order->quote_amount)->toBe(2000000)->and($order->total)->toBe(2360000)->and($order->hasOpenQuote())->toBeTrue();
    Notification::assertSentTo($order->academicStudent->user, QuoteReady::class);
});

test('ACA-9 the admin assigns an expert, who is emailed', function () {
    $order = AcademicOrder::factory()->paid()->create();
    $expert = Teacher::factory()->create(['offering' => 'academic']);

    Livewire::test(ViewAcademicOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('assign', data: ['expert_id' => $expert->id])
        ->assertHasNoActionErrors();

    expect($order->fresh()->expert_id)->toBe($expert->id);
    Notification::assertSentTo($expert->user, OrderAssigned::class);
});

test('ACA-7 the admin ships an order with a tracking number', function () {
    $order = AcademicOrder::factory()->paid()->create();
    $shipped = OrderStatus::where('name', 'Shipped')->sole();

    Livewire::test(ViewAcademicOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('changeStatus', data: ['status' => $shipped->id, 'courier' => 'DTDC', 'tracking' => 'D1234567'])
        ->assertHasNoActionErrors();

    expect($order->fresh()->status->is($shipped))->toBeTrue()
        ->and($order->fresh()->tracking_number)->toBe('D1234567')
        ->and($order->history()->latest('id')->first()->changed_by)->toBe($this->admin->id);
});

test('the order list shows open orders', function () {
    $open = AcademicOrder::factory()->create();
    $closed = AcademicOrder::factory()->create(['order_status_id' => OrderStatus::where('name', 'Cancelled')->sole()->id]);

    Livewire::test(ListAcademicOrders::class)->assertCanSeeTableRecords([$open])->assertCanNotSeeTableRecords([$closed]);
});

test('with online orders on, the CMS price list is edited elsewhere and kept on save', function () {
    config(['features.cms' => true]);
    $this->seed(ContentSeeder::class);
    $section = SiteSection::where('key', 'academic')->sole();
    $services = $section->content['services'];

    Livewire::test(EditSiteSection::class, ['record' => $section->getRouteKey()])
        ->assertSee('Academic → Services')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($section->fresh()->content['services'])->toBe($services);
});

test('non-admins cannot reach the academic screens', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get('/admin/academic-orders')->assertForbidden();
    $this->get('/admin/services')->assertForbidden();
    $this->get('/admin/order-statuses')->assertForbidden();
})->with(['teacher', 'academicStudent', 'parent']);
