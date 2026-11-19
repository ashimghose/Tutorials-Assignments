<?php

test('SEO the www address sends visitors to the main domain for good', function () {
    config(['app.url' => 'https://indianassignments.com']);

    $this->get('https://www.indianassignments.com/academic?utm_source=fb')
        ->assertStatus(301)
        ->assertRedirect('https://indianassignments.com/academic?utm_source=fb');
});

test('SEO the main domain is served as it is', function () {
    config(['app.url' => 'https://indianassignments.com']);

    $this->get('https://indianassignments.com/academic')->assertOk();
});
