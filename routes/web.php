<?php

use App\Http\Controllers\ClassJoinController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\SiteController;
use App\Support\LeadForms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Phase 0 public marketing site (MKT-1, MKT-6, CMS-2, CMS-4). Keep these
// exportable as static HTML: GET only, no session, forms or database reads.
Route::controller(SiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('tuition', 'tuition')->name('tuition');
    Route::get('academic', 'academic')->name('academic');
    Route::get('resources', 'resources')->name('resources.index');
    Route::get('resources/{slug}', 'article')->where('slug', '[a-z0-9-]+')->name('resources.show');
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
});

Route::view('about', 'site.about')->name('about');
Route::view('contact', 'site.contact')->name('contact');
Route::view('privacy', 'site.privacy')->name('privacy');
Route::view('terms', 'site.terms')->name('terms');
Route::view('refund-policy', 'site.refunds')->name('refunds');

// Cloudflare Pages shows /404.html for any address it doesn't have.
Route::view('404.html', 'site.not-found')->name('not-found');

// M4 (FEATURE_CMS): the site's own forms and campaign pages replace the
// Phase 0 Google Forms (CMS-3, MKT-2 to MKT-4).
Route::middleware('feature:cms')->controller(LeadController::class)->group(function () {
    Route::get('enquire/thanks', 'thanks')->name('enquire.thanks');
    Route::get('enquire/{form}', 'show')->whereIn('form', LeadForms::FORMS)->name('enquire.show');
    Route::post('enquire/{form}', 'store')->whereIn('form', LeadForms::FORMS)->middleware('throttle:leads')->name('enquire.store');
    Route::get('c/{campaign}', 'campaign')->name('campaign.show');
    Route::post('c/{campaign}', 'storeCampaign')->middleware('throttle:leads')->name('campaign.store');
});

// Phase 2 portal (M3). Each role reaches only its own pages (AUTH-5).
Route::middleware(['feature:accounts', 'auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (Request $request) => redirect()->route($request->user()->homeRoute()))
        ->name('dashboard');

    Route::middleware('role:parent')->prefix('parent')->name('parent.')->group(function () {
        Route::livewire('children', 'pages::portal.parent.children')->name('children');
        Route::livewire('classes', 'pages::portal.parent.classes')->middleware('feature:batches')->name('classes');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::livewire('/', 'pages::portal.student.home')->name('home');
    });

    Route::middleware('role:academic_student')->prefix('my-orders')->name('academic.')->group(function () {
        Route::view('/', 'portal.academic.home')->name('home');
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::livewire('/', 'pages::portal.teacher.home')->name('home');
    });

    // CLS-2, CLS-3: every Join click is checked on the server.
    Route::get('classes/{classSession}/join', ClassJoinController::class)->middleware('feature:batches')->name('classes.join');
});

// AUTH-4: a teacher sets their password from the emailed, signed invitation link.
Route::middleware(['feature:accounts', 'guest', 'signed'])->controller(InvitationController::class)->group(function () {
    Route::get('invitation/{user}', 'show')->name('invitation.show');
    Route::post('invitation/{user}', 'accept')->name('invitation.accept');
});

require __DIR__.'/settings.php';
