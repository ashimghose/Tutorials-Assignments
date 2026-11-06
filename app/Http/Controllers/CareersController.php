<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CaptureUtm;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Notifications\NewJobApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

/**
 * HIR-1 to HIR-3: the Careers pages. Job openings come from the admin
 * (Admin → Hiring → Job openings); applications go to Admin → Hiring →
 * Job applications with the campaign the visitor came from.
 */
class CareersController extends Controller
{
    public function index(): View
    {
        return view('site.careers.index', [
            'jobs' => JobOpening::query()->acceptingApplications()->orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }

    public function show(JobOpening $job): View
    {
        abort_unless($job->isAcceptingApplications(), 404);

        return view('site.careers.show', ['job' => $job]);
    }

    public function apply(StoreJobApplicationRequest $request, JobOpening $job): RedirectResponse
    {
        abort_unless($job->isAcceptingApplications(), 404);

        $utm = CaptureUtm::current($request);

        $application = new JobApplication([
            ...$request->safe()->only(['name', 'email', 'city', 'subjects', 'qualifications', 'experience', 'availability', 'link', 'message']),
            'mobile' => $request->normalisedMobile(),
            'languages' => array_values(array_unique((array) $request->validated('languages'))),
        ]);
        $application->forceFill([
            'job_opening_id' => $job->id,
            'job_title' => $job->title,
            'offering' => $job->offering,
            'declared_at' => now(),
            'utm_source' => $utm['source'] ?? null,
            'utm_medium' => $utm['medium'] ?? null,
            'utm_campaign' => $utm['campaign'] ?? null,
        ])->save();

        if (filled(config('site.contact.email'))) {
            Notification::route('mail', config('site.contact.email'))->notify(new NewJobApplication($application));
        }

        return redirect()->route('careers.thanks');
    }

    public function thanks(): View
    {
        return view('site.careers.thanks');
    }
}
