<?php

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Notifications\NewJobApplication;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(ContentSeeder::class);
    config(['verticals.music.enabled' => true, 'features.cms' => true, 'site.contact.email' => 'hello@example.com']);
});

function jobApplication(array $overrides = []): array
{
    return [
        'name' => 'Rina Das',
        'mobile' => '98765 43210',
        'email' => 'rina@example.com',
        'city' => 'Jorhat',
        'languages' => ['English', 'Hindi', 'Assamese'],
        'subjects' => 'Maths and Science, classes 8 to 10, SEBA',
        'qualifications' => 'MSc Maths, B.Ed',
        'experience' => 'Four years of home tuition for classes 8 to 10.',
        'availability' => 'Weekdays 5 to 9 pm',
        'link' => 'https://drive.google.com/file/d/abc/view',
        'declaration' => '1',
        ...$overrides,
    ];
}

test('HIR-1 the first six places are open: two academic, two tuition and two guitar teachers', function () {
    $jobs = JobOpening::query()->acceptingApplications()->get();

    expect($jobs)->toHaveCount(5)
        ->and($jobs->groupBy('offering')->map->sum('openings')->all())
        ->toEqual(['academic' => 2, 'tuition' => 2, 'music' => 2]);
});

test('HIR-1 the careers page lists open jobs by section and hides closed ones', function () {
    JobOpening::factory()->closed()->create(['title' => 'Chemistry teacher (closed)']);
    JobOpening::factory()->create(['title' => 'Biology teacher (later)', 'posted_on' => now()->addWeek()]);
    JobOpening::factory()->create(['title' => 'Hindi teacher (ended)', 'closes_on' => now()->subDays(2)]);

    $this->get(route('careers.index'))
        ->assertOk()
        ->assertSeeInOrder(['Academic services', 'Academic guidance expert', 'Tuition', 'Maths and Science teacher', 'Music lessons', 'Guitar teacher'])
        ->assertSee('2 places')
        ->assertDontSee('Chemistry teacher (closed)')
        ->assertDontSee('Biology teacher (later)')
        ->assertDontSee('Hindi teacher (ended)');

    $closed = JobOpening::where('title', 'Chemistry teacher (closed)')->sole();
    $this->get(route('careers.show', $closed))->assertNotFound();
    $this->post(route('careers.apply', $closed), jobApplication())->assertNotFound();
    expect(JobApplication::count())->toBe(0);
});

test('HIR-6 a job page shows the description, the apply form and Google job details', function () {
    $job = JobOpening::where('slug', 'guitar-teacher')->sole();

    $response = $this->get(route('careers.show', $job))
        ->assertOk()
        ->assertSee('Guitar teacher (online group classes)')
        ->assertSee('How hiring works')
        ->assertSee('English, Hindi and Assamese')
        ->assertSee('Link to a short video of you playing or singing')
        ->assertSee('"@type":"JobPosting"', false)
        ->assertSee('"jobLocationType":"TELECOMMUTE"', false)
        ->assertSee('"name":"Assam"', false);

    expect($response->getContent())->toContain('action="'.route('careers.apply', $job).'"');
});

test('HIR-1 the academic jobs say the work never includes writing for students to submit', function () {
    foreach (JobOpening::where('offering', 'academic')->get() as $job) {
        $this->get(route('careers.show', $job))
            ->assertSee('never includes writing assignments, papers or theses for students to submit as their own')
            ->assertSee('I understand this role never includes writing work for students to submit as their own.');
    }
});

test('HIR-2 HIR-3 an application is stored with its campaign source and emailed to the business', function () {
    Notification::fake();
    $job = JobOpening::where('slug', 'maths-science-teacher-classes-8-10')->sole();

    $this->get(route('careers.index', ['utm_source' => 'facebook', 'utm_medium' => 'group', 'utm_campaign' => 'assam-teachers']));

    $this->post(route('careers.apply', $job), jobApplication())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('careers.thanks'));

    $application = JobApplication::sole();
    expect($application->job_opening_id)->toBe($job->id)
        ->and($application->job_title)->toBe($job->title)
        ->and($application->offering)->toBe('tuition')
        ->and($application->mobile)->toBe('+919876543210')
        ->and($application->languages)->toBe(['English', 'Hindi', 'Assamese'])
        ->and($application->status)->toBe(JobApplicationStatus::New)
        ->and($application->declared_at)->not->toBeNull()
        ->and($application->utm_source)->toBe('facebook')
        ->and($application->utm_campaign)->toBe('assam-teachers');

    Notification::assertSentOnDemand(NewJobApplication::class);
    $this->get(route('careers.thanks'))->assertOk()->assertSee('Thank you for applying');
});

test('HIR-2 an application needs the main details, a valid link and the declaration', function () {
    $job = JobOpening::where('slug', 'guitar-teacher')->sole();

    $this->post(route('careers.apply', $job), jobApplication([
        'name' => '', 'email' => 'not-an-email', 'city' => '', 'languages' => [], 'link' => 'javascript:alert(1)', 'declaration' => null,
    ]))->assertSessionHasErrors(['name', 'email', 'city', 'languages', 'link', 'declaration']);

    $this->post(route('careers.apply', $job), jobApplication(['languages' => ['Klingon']]))
        ->assertSessionHasErrors(['languages.0']);

    $this->post(route('careers.apply', $job), jobApplication(['website' => 'spam.example']))
        ->assertSessionHasErrors(['website']);

    expect(JobApplication::count())->toBe(0);
});

test('HIR-2 the browser uses the server\'s own field rules', function () {
    $this->get(route('careers.show', 'guitar-teacher'))
        ->assertSee('id="job-name" name="name" type="text"', false)
        ->assertSee('maxlength="120"', false)
        ->assertSee('data-checks', false);
});

test('HIR-5 applications are deleted after twelve months', function () {
    $old = JobApplication::factory()->create(['created_at' => now()->subMonths(13)]);
    $recent = JobApplication::factory()->create(['created_at' => now()->subMonths(11)]);

    $this->artisan('model:prune', ['--model' => [JobApplication::class]])->assertSuccessful();

    expect(JobApplication::find($old->id))->toBeNull()
        ->and(JobApplication::find($recent->id))->not->toBeNull();
});

test('HIR-6 the menu and footer link to the careers page and the sitemap lists open jobs', function () {
    $this->get(route('home'))->assertSee(route('careers.index'))->assertSeeInOrder(['>About<', '>Careers<', '>Contact<'], false);
    $this->get(route('careers.index'))->assertSee('aria-current="page">Careers<', false);

    $this->get(route('sitemap'))
        ->assertSee(route('careers.index'))
        ->assertSee(route('careers.show', 'guitar-teacher'));
});

test('VRT-1 music jobs are hidden while the music section is switched off', function () {
    config(['verticals.music.enabled' => false]);

    $this->get(route('careers.index'))->assertOk()->assertDontSee('Guitar teacher');
    $this->get(route('careers.show', 'guitar-teacher'))->assertNotFound();
});

test('the careers pages are hidden while FEATURE_CMS is off', function () {
    config(['features.cms' => false]);

    $this->get(route('careers.index'))->assertNotFound();
    $this->post(route('careers.apply', 'guitar-teacher'), jobApplication())->assertNotFound();
    $this->get(route('home'))->assertDontSee(route('careers.index'));
});
