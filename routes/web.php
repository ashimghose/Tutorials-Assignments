<?php

use App\Http\Controllers\ClassJoinController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\OrderFileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UnsubscribeController;
use App\Support\LeadForms;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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

// Text pages: a title, intro and Markdown body each (CMS-1).
Route::view('about', 'site.page', ['key' => 'about'])->name('about');
Route::view('contact', 'site.contact')->name('contact');
Route::view('privacy', 'site.page', ['key' => 'privacy'])->name('privacy');
Route::view('terms', 'site.page', ['key' => 'terms'])->name('terms');
Route::view('refund-policy', 'site.page', ['key' => 'refunds'])->name('refunds');

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
        Route::livewire('/', 'pages::portal.academic.orders')->name('home');
        Route::middleware('feature:academic_orders')->group(function () {
            Route::livewire('new', 'pages::portal.academic.new-order')->name('orders.create');
            Route::livewire('{order}', 'pages::portal.academic.order')->whereNumber('order')->name('orders.show');
        });
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::livewire('/', 'pages::portal.teacher.home')->name('home');
        Route::middleware('feature:academic_orders')->group(function () {
            Route::livewire('orders', 'pages::portal.teacher.orders')->name('orders');
            Route::livewire('orders/{order}', 'pages::portal.teacher.order')->whereNumber('order')->name('orders.show');
        });
    });

    // ACA-8, ACA-9: private order files, through a short-lived signed link.
    Route::get('order-files/{orderFile}', OrderFileController::class)->middleware(['feature:academic_orders', 'signed'])->name('orders.files.download');

    // M7 (CHAT-1 to CHAT-5): messages with the team. Admins use the same pages as their inbox.
    Route::middleware('feature:chat')->group(function () {
        Route::livewire('messages', 'pages::portal.messages.index')->name('messages.index');
        Route::livewire('messages/{conversation}', 'pages::portal.messages.show')->whereNumber('conversation')->name('messages.show');
        Route::get('message-files/{message}', MessageAttachmentController::class)->middleware('signed')->name('messages.attachment');
    });

    // CLS-2, CLS-3: every Join click is checked on the server.
    Route::get('classes/{classSession}/join', ClassJoinController::class)->middleware('feature:batches')->name('classes.join');
});

// AUTH-4: a teacher sets their password from the emailed, signed invitation link.
Route::middleware(['feature:accounts', 'guest', 'signed'])->controller(InvitationController::class)->group(function () {
    Route::get('invitation/{user}', 'show')->name('invitation.show');
    Route::post('invitation/{user}', 'accept')->name('invitation.accept');
});

// NOT-3: one-click unsubscribe from the signed link in each marketing email.
// POST is the mail app's own one-click request (RFC 8058), so it has no CSRF token.
Route::match(['get', 'post'], 'unsubscribe/{type}/{id}', UnsubscribeController::class)
    ->middleware(['feature:chat', 'signed'])->withoutMiddleware(PreventRequestForgery::class)
    ->whereIn('type', ['user', 'lead'])->whereNumber('id')->name('unsubscribe');

require __DIR__.'/settings.php';
