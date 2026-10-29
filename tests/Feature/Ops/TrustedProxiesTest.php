<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_proxy-probe', fn (Request $request) => [
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
    ]);
});

test('a request through Cloudflare keeps the visitor\'s IP and HTTPS', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '172.70.1.1'])
        ->withHeaders(['X-Forwarded-For' => '49.36.1.2', 'X-Forwarded-Proto' => 'https'])
        ->get('/_proxy-probe')
        ->assertJson(['ip' => '49.36.1.2', 'secure' => true]);
});

test('anyone else cannot fake their IP or HTTPS with headers', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeaders(['X-Forwarded-For' => '49.36.1.2', 'X-Forwarded-Proto' => 'https'])
        ->get('/_proxy-probe')
        ->assertJson(['ip' => '203.0.113.9', 'secure' => false]);
});
