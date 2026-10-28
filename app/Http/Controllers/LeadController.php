<?php

namespace App\Http\Controllers;

use App\Enums\LeadKind;
use App\Http\Middleware\CaptureUtm;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Campaign;
use App\Models\Lead;
use App\Notifications\NewLead;
use App\Support\LeadForms;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * CMS-3, MKT-2, MKT-3, MKT-4: the site's own enquiry, demo and waitlist
 * forms and campaign landing pages (FEATURE_CMS). They replace the Phase 0
 * Google Forms; each lead keeps the campaign the visitor came from.
 */
class LeadController extends Controller
{
    public function show(Request $request, string $form): View
    {
        $section = $request->string('section')->toString();
        $offering = LeadForms::offering($form, $section);

        return view('site.enquire', [
            'form' => $form,
            'offering' => $offering,
            'kind' => LeadForms::kind($form, $request->string('interest')->toString()),
            'section' => in_array($section, ['tuition', 'academic'], true) ? $section : $offering,
            'interest' => mb_substr($request->string('interest')->toString(), 0, 160),
            'page' => mb_substr($request->string('page')->toString(), 0, 120),
            'priceRanges' => LeadForms::priceRanges($offering),
            'action' => route('enquire.store', $form),
        ]);
    }

    public function store(StoreLeadRequest $request, string $form): RedirectResponse
    {
        $this->record($request, $request->offering(), LeadForms::kind($form, $request->input('interest')));

        return redirect()->route('enquire.thanks', ['section' => $request->offering()]);
    }

    public function campaign(Campaign $campaign): View
    {
        abort_unless($campaign->is_active, 404);

        return view('site.campaign', [
            'campaign' => $campaign,
            'priceRanges' => LeadForms::priceRanges($campaign->offering),
        ]);
    }

    public function storeCampaign(StoreLeadRequest $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->is_active, 404);

        $this->record($request, $campaign->offering, LeadForms::kind($campaign->offering, $campaign->interest), $campaign);

        return redirect()->route('enquire.thanks', ['section' => $campaign->offering]);
    }

    public function thanks(Request $request): View
    {
        $section = $request->query('section') === 'academic' ? 'academic' : 'tuition';

        return view('site.enquire-thanks', ['section' => $section]);
    }

    private function record(StoreLeadRequest $request, string $offering, LeadKind $kind, ?Campaign $campaign = null): void
    {
        $utm = CaptureUtm::current($request);

        $lead = new Lead([
            ...$request->safe()->only(['name', 'email', 'class_or_service', 'message', 'price_range']),
            'mobile' => $request->normalisedMobile(),
            'interest' => $campaign !== null ? $campaign->interest : $request->input('interest'),
            'marketing_consent' => $request->boolean('marketing_consent'),
        ]);
        $lead->forceFill([
            'kind' => $kind,
            'offering' => $offering,
            'page' => $campaign ? "campaign:{$campaign->slug}" : $request->input('page'),
            'campaign_id' => $campaign?->id,
            'utm_source' => $utm['source'] ?? null,
            'utm_medium' => $utm['medium'] ?? null,
            'utm_campaign' => $utm['campaign'] ?? $campaign?->slug,
        ])->save();

        if (filled(config('site.contact.email'))) {
            Notification::route('mail', config('site.contact.email'))->notify(new NewLead($lead));
        }
    }
}
