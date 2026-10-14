<?php

use App\Http\Controllers\SiteController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
