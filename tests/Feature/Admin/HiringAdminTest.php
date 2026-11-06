<?php

use App\Enums\JobApplicationStatus;
use App\Filament\Resources\JobApplications\Pages\ListJobApplications;
use App\Filament\Resources\JobApplications\Pages\ViewJobApplication;
use App\Filament\Resources\JobOpenings\Pages\CreateJobOpening;
use App\Filament\Resources\JobOpenings\Pages\EditJobOpening;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\AuditLog;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\TeacherInvitation;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    config(['verticals.music.enabled' => true, 'features.cms' => true]);
    $this->seed(ContentSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('HIR-1 the admin adds a job and it appears on the careers page', function () {
    Livewire::test(CreateJobOpening::class)
        ->fillForm([
            'title' => 'Keyboard teacher',
            'slug' => 'keyboard-teacher',
            'summary' => 'Teach keyboard to small online groups.',
            'offering' => 'music',
            'openings' => 1,
            'is_open' => true,
            'location' => 'Online, from anywhere in Assam',
            'region' => 'Assam',
            'languages' => 'English, Hindi and Assamese',
            'pay' => 'About 50% of your group\'s fees.',
            'posted_on' => now()->toDateString(),
            'body' => "## What you will do\n\n- Teach keyboard basics.",
            'sort_order' => 50,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->get(route('careers.index'))->assertSee('Keyboard teacher');
    $this->get(route('careers.show', 'keyboard-teacher'))->assertOk()->assertSee('Teach keyboard basics.', false);
    expect(AuditLog::where('subject_type', (new JobOpening)->getMorphClass())->exists())->toBeTrue();
});

test('HIR-1 the admin edits a job\'s text and closes it', function () {
    $job = JobOpening::where('slug', 'guitar-teacher')->sole();

    Livewire::test(EditJobOpening::class, ['record' => $job->getRouteKey()])
        ->fillForm(['pay' => '60% of your group\'s fees, paid monthly.'])
        ->call('save')
        ->assertHasNoFormErrors();
    $this->get(route('careers.show', $job))->assertSee('60% of your group&#039;s fees, paid monthly.', false);

    Livewire::test(EditJobOpening::class, ['record' => $job->getRouteKey()])
        ->fillForm(['is_open' => false])
        ->call('save')
        ->assertHasNoFormErrors();
    $this->get(route('careers.show', $job))->assertNotFound();
    $this->get(route('careers.index'))->assertDontSee('Guitar teacher');
});

test('HIR-1 a job\'s web address must be unique and simple', function () {
    Livewire::test(CreateJobOpening::class)
        ->fillForm(['title' => 'Guitar teacher', 'slug' => 'Guitar Teacher!', 'summary' => 'x', 'offering' => 'music', 'location' => 'x', 'region' => 'Assam', 'languages' => 'x', 'pay' => 'x', 'body' => 'x'])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    Livewire::test(CreateJobOpening::class)
        ->fillForm(['title' => 'Guitar teacher', 'slug' => 'guitar-teacher', 'summary' => 'x', 'offering' => 'music', 'location' => 'x', 'region' => 'Assam', 'languages' => 'x', 'pay' => 'x', 'body' => 'x'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

test('HIR-4 the admin sees applications, marks them and invites a hired person as a teacher', function () {
    Notification::fake();
    $job = JobOpening::where('slug', 'guitar-teacher')->sole();
    $application = JobApplication::factory()->create([
        'job_opening_id' => $job->id, 'job_title' => $job->title, 'offering' => 'music',
        'name' => 'Bikash Gogoi', 'email' => 'bikash@example.com',
    ]);

    Livewire::test(ListJobApplications::class)->assertCanSeeTableRecords([$application]);
    Livewire::test(ListJobApplications::class)->callTableAction('mark_shortlisted', $application);
    expect($application->fresh()->status)->toBe(JobApplicationStatus::Shortlisted);

    Livewire::test(ViewJobApplication::class, ['record' => $application->getRouteKey()])
        ->assertSee('Bikash Gogoi')
        ->callAction('invite', data: ['name' => 'Bikash Gogoi', 'email' => 'bikash@example.com', 'offerings' => ['music'], 'subjects' => ['Guitar']])
        ->assertHasNoActionErrors();

    $teacher = User::where('email', 'bikash@example.com')->sole();
    expect($teacher->teacher->offerings)->toBe(['music'])
        ->and($teacher->teacher->subjects)->toBe(['Guitar'])
        ->and($application->fresh()->status)->toBe(JobApplicationStatus::Hired)
        ->and(AuditLog::where('action', 'job_application.invited')->exists())->toBeTrue();
    Notification::assertSentTo($teacher, TeacherInvitation::class);

    Livewire::test(ViewJobApplication::class, ['record' => $application->getRouteKey()])->assertActionHidden('invite');
});

test('HIR-4 only admins reach the hiring screens, and nobody adds applications by hand', function () {
    $application = JobApplication::factory()->create();

    $this->get('/admin/job-applications')->assertOk();
    $this->get('/admin/job-openings')->assertOk();
    expect($this->admin->can('create', JobApplication::class))->toBeFalse();

    $teacher = User::factory()->teacher()->create();
    $this->actingAs($teacher);
    $this->get('/admin/job-applications')->assertForbidden();
    $this->get("/admin/job-applications/{$application->id}")->assertForbidden();
    expect($teacher->can('view', $application))->toBeFalse()
        ->and($teacher->can('update', JobOpening::first()))->toBeFalse();
});

test('HIR-7 the teachers list has a tab for each section with how many teachers it has', function () {
    $guitar = Teacher::factory()->create(['offerings' => ['music']]);
    $tutor = Teacher::factory()->create(['offerings' => ['tuition']]);
    $both = Teacher::factory()->create(['offerings' => ['academic', 'tuition']]);

    Livewire::test(ListTeachers::class)
        ->assertCanSeeTableRecords([$guitar, $tutor, $both])
        ->set('activeTab', 'music')
        ->assertCanSeeTableRecords([$guitar])
        ->assertCanNotSeeTableRecords([$tutor, $both])
        ->set('activeTab', 'tuition')
        ->assertCanSeeTableRecords([$tutor, $both])
        ->assertCanNotSeeTableRecords([$guitar]);
});
