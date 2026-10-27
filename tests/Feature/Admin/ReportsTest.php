<?php

use App\Actions\Money\RecordPayment;
use App\Enums\ClassRatePer;
use App\Enums\ClassSessionStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\ExpenseCategory;
use App\Enums\Offering;
use App\Enums\OrderRateType;
use App\Enums\PaymentMethod;
use App\Filament\Pages\MoneyReport;
use App\Filament\Pages\OrdersReport;
use App\Filament\Pages\Payouts as PayoutsPage;
use App\Filament\Pages\Subscriptions;
use App\Filament\Resources\AcademicOrders\Pages\ViewAcademicOrder;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\Enrolments\Pages\ListEnrolments;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AcademicOrder;
use App\Models\AcademicOrderAddon;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Enrolment;
use App\Models\Expense;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MoneyReport as Report;
use App\Services\Payouts;
use Carbon\CarbonImmutable;
use Database\Seeders\AcademicCatalogueSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->seed(AcademicCatalogueSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00', 'Asia/Kolkata'));
    $this->october = CarbonImmutable::parse('2026-10-01', 'Asia/Kolkata');
});

/**
 * A class in October 2026 at the given India time, 60 minutes long unless said otherwise.
 */
function octoberClass(Batch $batch, string $start, ?string $teacherJoined, int $minutes = 60, array $attributes = []): ClassSession
{
    // Saved in UTC, as the app stores times.
    $startsAt = CarbonImmutable::parse("2026-10-{$start}", 'Asia/Kolkata')->utc();
    $session = ClassSession::factory()->for($batch)->create(['starts_at' => $startsAt, 'ends_at' => $startsAt->addMinutes($minutes)]);
    $session->forceFill([
        'started_at' => $teacherJoined ? CarbonImmutable::parse("2026-10-{$teacherJoined}", 'Asia/Kolkata')->utc() : null,
        ...$attributes,
    ])->save();

    return $session;
}

function completedOrder(Teacher $expert, string $completed, array $attributes = [], string $status = 'Delivered'): AcademicOrder
{
    $order = AcademicOrder::factory()->paid()->create(['expert_id' => $expert->id]);
    $order->forceFill([
        'completed_at' => CarbonImmutable::parse($completed, 'Asia/Kolkata')->utc(),
        'order_status_id' => OrderStatus::where('name', $status)->sole()->id,
        ...$attributes,
    ])->save();

    return $order;
}

test('ADM-2 the month\'s class pay matches a hand calculation', function () {
    $teacher = Teacher::factory()->create(['class_rate' => 50000, 'class_rate_per' => ClassRatePer::PerClass]);
    $batch = Batch::factory()->for($teacher)->create();

    octoberClass($batch, '05 17:00', '05 16:58');                 // counts
    octoberClass($batch, '07 17:00', '07 17:05');                 // counts: 55 minutes
    octoberClass($batch, '12 17:00', '12 17:40');                 // only 20 minutes: no
    octoberClass($batch, '14 17:00', '14 17:00', attributes: ['ended_at' => CarbonImmutable::parse('2026-10-14 17:20', 'Asia/Kolkata')->utc()]); // ended early: no
    octoberClass($batch, '19 17:00', null);                       // never started: no
    octoberClass($batch, '21 17:00', '21 17:00', attributes: ['status' => ClassSessionStatus::Cancelled]); // cancelled: no
    octoberClass($batch, '31 23:00', '31 23:00');                 // 31 Oct in India (UTC: 31 Oct 17:30): counts
    ClassSession::factory()->for($batch)->create(['starts_at' => CarbonImmutable::parse('2026-11-01 00:30', 'Asia/Kolkata')->utc(), 'ends_at' => CarbonImmutable::parse('2026-11-01 01:30', 'Asia/Kolkata')->utc()])
        ->forceFill(['started_at' => CarbonImmutable::parse('2026-11-01 00:30', 'Asia/Kolkata')->utc()])->save(); // November in India

    $line = app(Payouts::class)->line($teacher, $this->october);

    expect($line->classes)->toHaveCount(3)
        ->and($line->classAmount)->toBe(3 * 50000)
        ->and($line->orderAmount)->toBe(0);
});

test('ADM-1 ADM-2 an hourly rate pays the class\'s scheduled length', function () {
    $teacher = Teacher::factory()->create(['class_rate' => 60000, 'class_rate_per' => ClassRatePer::PerHour]);
    $batch = Batch::factory()->for($teacher)->create();
    octoberClass($batch, '05 17:00', '05 17:00', 90);
    octoberClass($batch, '06 17:00', '06 17:00', 45);

    // ₹600 × 1.5 h + ₹600 × 0.75 h = ₹900 + ₹450
    expect(app(Payouts::class)->line($teacher, $this->october)->classAmount)->toBe(90000 + 45000);
});

test('ACA-10 ADM-2 experts are paid for paid orders completed that month, not cancelled ones', function () {
    $percent = Teacher::factory()->create(['offering' => Offering::Academic, 'order_rate_type' => OrderRateType::Percent, 'order_rate' => 40]);
    $fixed = Teacher::factory()->create(['offering' => Offering::Academic, 'order_rate_type' => OrderRateType::Fixed, 'order_rate' => 150000]);

    // ₹8,000 subtotal of which ₹1,000 is printing: 40% of ₹7,000 = ₹2,800.
    $withPrinting = completedOrder($percent, '2026-10-10 15:00');
    AcademicOrderAddon::query()->insert(['academic_order_id' => $withPrinting->id, 'name' => 'Spiral binding', 'kind' => 'printing', 'unit_price' => 50000, 'quantity' => 2, 'line_total' => 100000]);
    completedOrder($percent, '2026-10-20 15:00', status: 'Cancelled');
    completedOrder($percent, '2026-10-21 15:00', ['paid_at' => null]);
    completedOrder($percent, '2026-11-02 15:00');

    completedOrder($fixed, '2026-10-03 10:00');
    completedOrder($fixed, '2026-10-30 10:00');

    expect(app(Payouts::class)->line($percent, $this->october)->orderAmount)->toBe(280000)
        ->and(app(Payouts::class)->line($percent, $this->october)->orders)->toHaveCount(1)
        ->and(app(Payouts::class)->line($fixed, $this->october)->orderAmount)->toBe(300000);
});

test('ADM-2 the admin marks a month as paid, which keeps the amounts', function () {
    $teacher = Teacher::factory()->create(['class_rate' => 50000]);
    $batch = Batch::factory()->for($teacher)->create();
    octoberClass($batch, '05 17:00', '05 17:00');
    $idle = Teacher::factory()->create(['class_rate' => 50000]);

    Livewire::test(PayoutsPage::class)
        ->filterTable('month', '2026-10')
        ->assertSee($teacher->user->name)
        ->assertDontSee($idle->user->name)
        ->assertSee('₹500')
        ->callTableAction('markPaid', (string) $teacher->id, data: ['paid_on' => '2026-11-05', 'reference' => 'UTR123'])
        ->assertHasNoTableActionErrors()
        ->assertSee('5 Nov 2026 · UTR123');

    $payout = Payout::sole();
    expect($payout->total)->toBe(50000)
        ->and($payout->classes)->toBe(1)
        ->and($payout->month->toDateString())->toBe('2026-10-01');

    // A class recorded later doesn't change what was paid.
    octoberClass($batch, '06 17:00', '06 17:00');
    Livewire::test(PayoutsPage::class)->filterTable('month', '2026-10')
        ->assertTableActionHidden('markPaid', (string) $teacher->id);
});

test('ADM-1 the admin sets a teacher\'s pay in rupees or as a percentage', function () {
    $teacher = Teacher::factory()->create(['offering' => Offering::Both]);

    Livewire::test(EditTeacher::class, ['record' => $teacher->getRouteKey()])
        ->fillForm(['class_rate' => 600, 'class_rate_per' => 'hour', 'order_rate_type' => 'percent'])
        ->fillForm(['order_rate' => 35])
        ->call('save')
        ->assertHasNoFormErrors();

    $teacher->refresh();
    expect($teacher->class_rate)->toBe(60000)
        ->and($teacher->class_rate_per)->toBe(ClassRatePer::PerHour)
        ->and($teacher->order_rate_type)->toBe(OrderRateType::Percent)
        ->and($teacher->order_rate)->toBe(35);

    Livewire::test(EditTeacher::class, ['record' => $teacher->getRouteKey()])
        ->fillForm(['order_rate_type' => 'fixed'])
        ->fillForm(['order_rate' => 1200])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($teacher->fresh()->order_rate)->toBe(120000);
});

test('ADM-2 the admin records class times when the teacher forgot to press Join', function () {
    $batch = Batch::factory()->create();
    $class = octoberClass($batch, '05 17:00', null);

    Livewire::test(SessionsRelationManager::class, ['ownerRecord' => $batch, 'pageClass' => EditBatch::class])
        ->filterTable('upcoming', false)
        ->callTableAction('times', $class, data: ['started_at' => '2026-10-05 17:02:00', 'ended_at' => null])
        ->assertHasNoTableActionErrors();

    expect($class->fresh()->started_at->setTimezone('Asia/Kolkata')->format('Y-m-d H:i'))->toBe('2026-10-05 17:02');
});

test('ADM-4 "Fee received" and "Payment received" record the money', function () {
    $enrolment = Enrolment::factory()->create();
    Livewire::test(ListEnrolments::class)
        ->callTableAction('activate', $enrolment, data: ['paid_through' => '2026-12-14', 'method' => 'upi', 'reference' => 'UTR9'])
        ->assertHasNoTableActionErrors();

    $fee = Payment::sole();
    expect($fee->amount)->toBe($enrolment->batch->monthly_fee)
        ->and($fee->offering)->toBe(Offering::Tuition)
        ->and($fee->method)->toBe(PaymentMethod::Upi)
        ->and($fee->enrolment_id)->toBe($enrolment->id);

    $order = AcademicOrder::factory()->create();
    Livewire::test(ViewAcademicOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('markPaid', data: ['gateway_fee' => 223])
        ->assertHasNoActionErrors();

    $payment = Payment::where('academic_order_id', $order->id)->sole();
    expect($payment->amount)->toBe($order->total)
        ->and($payment->gateway_fee)->toBe(22300)
        ->and($payment->offering)->toBe(Offering::Academic)
        ->and($order->fresh()->isPaid())->toBeTrue();
});

test('ADM-4 the money report, split by offering, matches a hand calculation', function () {
    $tuitionTeacher = Teacher::factory()->create();
    $expert = Teacher::factory()->create(['offering' => Offering::Academic]);
    $enrolment = Enrolment::factory()->create();
    $order = AcademicOrder::factory()->create();

    $pay = fn ($for, int $rupees, string $on, int $fee = 0) => app(RecordPayment::class)
        ->handle($for, $rupees * 100, PaymentMethod::PaymentLink, CarbonImmutable::parse($on), gatewayFee: $fee * 100);
    $pay($enrolment, 1500, '2026-10-02', 30);
    $pay($enrolment, 1500, '2026-10-30', 30);
    $pay($enrolment, 1500, '2026-11-01');      // outside the range
    $pay($order, 9440, '2026-10-10', 190);

    Expense::create(['offering' => 'academic', 'category' => ExpenseCategory::Printing, 'amount' => 80000, 'spent_on' => '2026-10-12']);
    Expense::create(['offering' => 'academic', 'category' => ExpenseCategory::Courier, 'amount' => 12000, 'spent_on' => '2026-10-13']);
    Expense::create(['offering' => 'tuition', 'category' => ExpenseCategory::Other, 'amount' => 50000, 'spent_on' => '2026-10-01']);

    foreach ([[$tuitionTeacher, 200000, 0], [$expert, 0, 280000]] as [$teacher, $classes, $orders]) {
        (new Payout)->forceFill(['teacher_id' => $teacher->id, 'month' => '2026-09-01', 'class_amount' => $classes, 'order_amount' => $orders, 'total' => $classes + $orders, 'paid_on' => '2026-10-05'])->save();
    }

    $report = app(Report::class)->between(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'));

    // Tuition: 3,000 in − 60 fees − 2,000 teacher − 500 other = 440
    expect($report['tuition'])->toBe(['received' => 300000, 'gateway_fees' => 6000, 'paid_out' => 200000, 'printing_courier' => 0, 'other_costs' => 50000, 'net' => 44000])
        // Academic: 9,440 in − 190 fees − 2,800 expert − 920 printing and courier = 5,530
        ->and($report['academic'])->toBe(['received' => 944000, 'gateway_fees' => 19000, 'paid_out' => 280000, 'printing_courier' => 92000, 'other_costs' => 0, 'net' => 553000])
        ->and($report['total']['net'])->toBe(597000);

    Livewire::test(MoneyReport::class)
        ->filterTable('period', ['from' => '2026-10-01', 'to' => '2026-10-31'])
        ->assertSee('₹5,530')
        ->assertSee('₹5,970');
});

test('ADM-4 the orders report counts orders by status, service and expert', function () {
    $expert = Teacher::factory()->create(['offering' => Offering::Academic]);
    AcademicOrder::factory()->count(2)->paid()->create(['expert_id' => $expert->id, 'created_at' => '2026-11-10 06:00:00']);
    AcademicOrder::factory()->create(['service_title' => 'Similarity check', 'created_at' => '2026-11-11 06:00:00']);
    AcademicOrder::factory()->create(['service_title' => 'Old order', 'created_at' => '2026-09-01 06:00:00']);

    $report = app(Report::class)->orders(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-15'));
    expect($report['service']->all())->toBe(['Thesis editing and proofreading' => 2, 'Similarity check' => 1])
        ->and($report['expert']->all())->toBe([$expert->user->name => 2, 'Not assigned' => 1])
        ->and($report['status']->all())->toBe([OrderStatus::afterPayment()->name => 2, OrderStatus::initial()->name => 1]);

    Livewire::test(OrdersReport::class)->assertSee('Similarity check')->assertDontSee('Old order');
});

test('ADM-3 the subscription report counts students per batch by status', function () {
    $batch = Batch::factory()->create();
    Enrolment::factory()->count(2)->active(10)->create(['batch_id' => $batch->id]);
    Enrolment::factory()->create(['batch_id' => $batch->id]);
    Enrolment::factory()->create(['batch_id' => $batch->id, 'status' => EnrolmentStatus::Expired]);

    Livewire::test(Subscriptions::class)
        ->assertCanSeeTableRecords([$batch])
        ->assertSee('3 / 15')
        ->assertSee(Enrolment::today()->addDays(10)->format('j M Y'));
});

test('ADM-5 people lists filter active and past, and download as CSV', function () {
    $current = Teacher::factory()->create()->user;
    $former = Teacher::factory()->create(['active_to' => '2026-06-30'])->user;
    $enrolled = tuitionChild();
    Enrolment::factory()->active()->create(['student_id' => $enrolled->id]);
    $lapsed = tuitionChild();
    Enrolment::factory()->create(['student_id' => $lapsed->id, 'status' => EnrolmentStatus::Expired]);

    Livewire::test(ListUsers::class)
        ->filterTable('standing', 'active')
        ->assertCanSeeTableRecords([$current, $enrolled->user, $enrolled->parent->user])
        ->assertCanNotSeeTableRecords([$former, $lapsed->user])
        ->filterTable('standing', 'past')
        ->assertCanSeeTableRecords([$former, $lapsed->user, $lapsed->parent->user])
        ->assertCanNotSeeTableRecords([$current, $enrolled->user])
        ->callAction('exportCsv')
        ->assertFileDownloaded('people-2026-11-15.csv');

    expect(AuditLog::where('action', 'users.export')->exists())->toBeTrue();
});

test('only admins reach the reports', function (string $path) {
    $this->actingAs(Teacher::factory()->create()->user)->get($path)->assertForbidden();
    $this->actingAs($this->admin)->get($path)->assertOk();
})->with(['/admin/payouts', '/admin/money-report', '/admin/orders-report', '/admin/subscriptions', '/admin/payments', '/admin/expenses']);
