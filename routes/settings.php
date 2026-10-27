<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['feature:accounts', 'auth'])->group(function () {
    Route::get('settings', fn () => redirect()->route(auth()->user()->isStudent() ? 'security.edit' : 'profile.edit'));

    // A school student's details and account belong to their parent.
    Route::livewire('settings/profile', 'pages::settings.profile')
        ->middleware('role:parent,academic_student,teacher,admin')
        ->name('profile.edit');
});

Route::middleware(['feature:accounts', 'auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
