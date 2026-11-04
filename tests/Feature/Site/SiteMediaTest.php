<?php

use App\Models\SiteSection;
use App\Support\SiteContent;
use App\Support\SiteMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['features.cms' => true, 'site.media_disk' => 'media-test']);
    Storage::fake('media-test');
});

function uploadSitePhoto(string $name = 'photo.jpg', int $width = 1600, int $height = 900): string
{
    $path = SiteMedia::DIRECTORY.'/originals/'.pathinfo($name, PATHINFO_FILENAME).'.jpg';
    Storage::disk('media-test')->put($path, UploadedFile::fake()->image($name, $width, $height)->getContent());

    return $path;
}

function tuitionWithPhoto(?string $image, string $alt = 'A child in an online class'): SiteSection
{
    $content = SiteContent::sectionFile('tuition');
    unset($content['teachers'], $content['testimonials']);
    $content['photos'][0] = ['image' => $image, 'alt' => $alt];

    return SiteSection::create(['key' => 'tuition', 'content' => $content]);
}

test('CMS-1 an uploaded photo is cropped to 4:3 and saved as two WebP copies', function () {
    $image = uploadSitePhoto('class.jpg', 1600, 900);

    tuitionWithPhoto($image);

    foreach (SiteMedia::WIDTHS as $width) {
        $file = SiteMedia::variant($image, $width);
        Storage::disk('media-test')->assertExists($file);
        [$w, $h, $type] = getimagesizefromstring(Storage::disk('media-test')->get($file));
        expect([$w, $h, $type])->toBe([$width, intdiv($width * 3, 4), IMAGETYPE_WEBP]);
    }
});

test('CMS-1 the page shows the uploaded photo, and the built-in ones in empty slots', function () {
    tuitionWithPhoto(uploadSitePhoto('class.jpg'), 'Our new class photo');

    $this->get('/tuition')->assertOk()
        ->assertSee('<img src="'.route('media', ['path' => 'class-640.webp']).'"', false)
        ->assertSee('alt="Our new class photo"', false)
        ->assertSee('/images/photos/tuition-teacher-whiteboard-640.webp', false)
        ->assertDontSee('/images/photos/tuition-girl-online-class-640.webp', false);
});

test('CMS-1 with no uploads the built-in photos stay', function () {
    tuitionWithPhoto(null, 'A school student takes notes during a live online class with a teacher on her laptop');

    $this->get('/tuition')->assertOk()
        ->assertSee('<img src="/images/photos/tuition-girl-online-class-640.webp"', false);
});

test('CMS-1 /media serves the WebP copy for a year without cookies', function () {
    $image = uploadSitePhoto('class.jpg');
    tuitionWithPhoto($image);

    $response = $this->get('/media/class-1200.webp')->assertOk()
        ->assertHeader('Content-Type', 'image/webp');

    expect($response->headers->get('Cache-Control'))->toContain('max-age=31536000')
        ->and($response->headers->getCookies())->toBe([]);
});

test('CMS-1 /media only serves existing WebP copies', function (string $path) {
    uploadSitePhoto('class.jpg');

    $this->get($path)->assertNotFound();
})->with([
    'missing copy' => ['/media/nothing-640.webp'],
    'the original' => ['/media/originals/class.jpg'],
    'other size' => ['/media/class-300.webp'],
    'path tricks' => ['/media/..%2Foriginals%2Fclass-640.webp'],
]);

test('CMS-1 a file that is not an image is refused when the page is saved', function () {
    Storage::disk('media-test')->put(SiteMedia::DIRECTORY.'/originals/bad.jpg', 'not an image');

    tuitionWithPhoto(SiteMedia::DIRECTORY.'/originals/bad.jpg');
})->throws(RuntimeException::class);

test('CMS-1 the admin preview of a saved photo loads from this site, so it can be removed', function () {
    $image = uploadSitePhoto('class.jpg');
    tuitionWithPhoto($image);

    $preview = SiteMedia::preview($image);

    expect($preview['url'])->toBe(SiteMedia::url($image, 640))
        ->and($preview['url'])->toStartWith(config('app.url'))
        ->and($preview['type'])->toBe('image/webp')
        ->and($preview['size'])->toBeGreaterThan(0)
        ->and(SiteMedia::preview('site-media/originals/missing.jpg'))->toBeNull();

    $this->get($preview['url'])->assertOk();
});
