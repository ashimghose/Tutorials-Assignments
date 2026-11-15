<?php

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Models\SiteSection;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\SiteMedia;
use Database\Seeders\ContentSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['features.cms' => true, 'site.media_disk' => 'media-test']);
    Storage::fake('media-test');
    $this->seed(ContentSeeder::class);
});

/**
 * The academic page as the live site saved it before samples existed.
 *
 * @param  list<array<string, mixed>>|null  $samples
 */
function academicWithSamples(?array $samples): SiteSection
{
    $section = SiteSection::where('key', 'academic')->sole();
    $content = $section->content;
    unset($content['samples'], $content['text']['samples_eyebrow'], $content['text']['samples_title'],
        $content['text']['samples_lead'], $content['text']['samples_more'], $content['text']['samples_open']);
    if ($samples !== null) {
        $content['samples'] = $samples;
    }
    $section->update(['content' => $content]);

    return $section->fresh();
}

function storeSamplePdf(string $name = '01JSAMPLEPDF.pdf', string $bytes = "%PDF-1.7\n%sample\n"): string
{
    $path = SiteMedia::SAMPLES_DIRECTORY.'/'.$name;
    Storage::disk('media-test')->put($path, $bytes);

    return $path;
}

test('MKT-1 the samples section stays hidden until the admin adds one', function () {
    academicWithSamples(null);

    $this->get(route('academic'))->assertOk()->assertDontSee('id="samples"', false);
});

test('MKT-1 academic samples show videos and PDFs, three at first and the rest under See all', function () {
    $pdf = storeSamplePdf();
    academicWithSamples([
        ['title' => 'Class 12 Physics: worked solutions', 'kind' => 'pdf', 'file' => $pdf, 'level' => 'Class 12', 'subject' => 'Physics', 'note' => 'Ten practice questions'],
        ['title' => 'Formatting a thesis chapter', 'kind' => 'video', 'url' => 'https://youtube.com/shorts/Yzcf6-dT8ow', 'level' => 'PhD', 'subject' => ''],
        ['title' => 'Checking citations', 'kind' => 'video', 'url' => 'https://youtu.be/mPaxGO480bY'],
        ['title' => 'Fourth sample', 'kind' => 'video', 'url' => 'https://youtu.be/07HMkj8A5VY'],
        ['title' => 'Not a YouTube link', 'kind' => 'video', 'url' => 'https://example.com/video.mp4'],
    ]);

    $response = $this->get(route('academic'))->assertOk()
        ->assertSeeInOrder(['id="samples"', 'See how we guide', 'Class 12 Physics: worked solutions', 'Checking citations', 'data-more-samples', 'See all samples (5)', 'Fourth sample', 'id="faqs"'], false)
        ->assertSee(SiteMedia::sampleUrl($pdf), false)
        ->assertSee('Class 12 · Physics')
        ->assertSee('Open the PDF')
        ->assertSee('data-youtube="Yzcf6-dT8ow"', false)
        ->assertDontSee('<iframe', false)
        ->assertDontSee('Not a YouTube link');

    $html = $response->getContent();
    expect(substr_count($html, 'data-sample="pdf"'))->toBe(1)
        ->and(substr_count($html, 'data-sample="video"'))->toBe(3)
        ->and(substr_count($html, 'aspect-[9/16]'))->toBe(1);
});

test('MKT-1 three samples or fewer need no See all button', function () {
    academicWithSamples([
        ['title' => 'Checking citations', 'kind' => 'video', 'url' => 'https://youtu.be/mPaxGO480bY'],
    ]);

    $this->get(route('academic'))->assertOk()->assertSee('Checking citations')->assertDontSee('data-more-samples', false);
});

test('MKT-1 a sample PDF is served as a PDF without a session', function () {
    $pdf = storeSamplePdf();

    $this->get(SiteMedia::sampleUrl($pdf))->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertCookieMissing(config('session.cookie'));
});

test('MKT-1 only real PDFs in the samples folder are served', function (string $url) {
    storeSamplePdf('FAKE.pdf', '<html>not a pdf</html>');
    Storage::disk('media-test')->put(SiteMedia::DIRECTORY.'/originals/secret.pdf', "%PDF-1.7\n");

    $this->get($url)->assertNotFound();
})->with([
    'missing file' => ['/samples/missing.pdf'],
    'not a PDF inside' => ['/samples/FAKE.pdf'],
    'another folder' => ['/samples/..%2Foriginals%2Fsecret.pdf'],
    'not a .pdf name' => ['/samples/FAKE.html'],
]);

test('MKT-1 the admin adds a PDF sample and a video sample', function () {
    $academic = academicWithSamples(null);
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $academic->getRouteKey()])
        ->fillForm(function (array $state) {
            $state['content']['samples'] = [
                ['title' => 'Formatting a thesis chapter', 'kind' => 'video', 'url' => 'https://youtu.be/mPaxGO480bY', 'tall' => false, 'level' => 'PhD', 'subject' => '', 'note' => ''],
                ['title' => 'Class 12 Physics: worked solutions', 'kind' => 'pdf', 'file' => [UploadedFile::fake()->create('physics.pdf', 200, 'application/pdf')], 'level' => 'Class 12', 'subject' => 'Physics', 'note' => ''],
            ];

            return $state;
        })->call('save')->assertHasNoFormErrors();

    $samples = $academic->fresh()->content['samples'];
    expect($samples[0])->kind->toBe('video')->url->toBe('https://youtu.be/mPaxGO480bY')
        ->and($samples[1]['kind'])->toBe('pdf')
        ->and($samples[1]['file'])->toStartWith(SiteMedia::SAMPLES_DIRECTORY.'/');
    Storage::disk('media-test')->assertExists($samples[1]['file']);
});

test('MKT-1 a video sample needs a YouTube link', function () {
    $academic = academicWithSamples(null);
    $this->actingAs(User::factory()->admin()->create());
    Repeater::fake();

    Livewire::test(EditSiteSection::class, ['record' => $academic->getRouteKey()])
        ->fillForm(function (array $state) {
            $state['content']['samples'] = [['title' => 'Video', 'kind' => 'video', 'url' => 'https://example.com/a.mp4', 'tall' => false]];

            return $state;
        })->call('save')->assertHasFormErrors(['content.samples.0.url']);
});

test('CMS-1 a testimonial screenshot keeps its shape and shows with the quote', function () {
    $image = SiteMedia::DIRECTORY.'/testimonials/chat.png';
    Storage::disk('media-test')->put($image, UploadedFile::fake()->image('chat.png', 1080, 2000)->getContent());

    Testimonial::create(['offering' => 'academic', 'quote' => 'The formatting was perfect, thank you', 'image' => $image, 'name' => 'Priya S.', 'detail' => 'MBA student', 'is_published' => true]);

    [$w, $h, $type] = getimagesizefromstring(Storage::disk('media-test')->get(SiteMedia::shotVariant($image)));
    expect([$w, $h, $type])->toBe([720, 1333, IMAGETYPE_WEBP]);

    $this->get(route('academic'))->assertOk()
        ->assertSeeInOrder(['id="testimonials"', SiteMedia::shotUrl($image), 'The formatting was perfect, thank you', 'Priya S.'], false);
    $this->get(SiteMedia::shotUrl($image))->assertOk()->assertHeader('Content-Type', 'image/webp');
});

test('CMS-1 a testimonial without a screenshot still shows as a quote', function () {
    Testimonial::create(['offering' => 'academic', 'quote' => 'Clear guidance on my synopsis', 'name' => 'Rahul D.', 'is_published' => true]);

    $this->get(route('academic'))->assertOk()->assertSee('Clear guidance on my synopsis')->assertDontSee('-shot.webp', false);
});

test('CMS-1 the admin adds a testimonial with a screenshot; a non-admin cannot', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateTestimonial::class)
        ->fillForm([
            'quote' => 'Thank you for checking my references',
            'image' => [UploadedFile::fake()->image('chat.jpg', 800, 1400)],
            'name' => 'Anu K.',
            'offering' => 'academic',
            'is_published' => true,
        ])->call('create')->assertHasNoFormErrors();

    $testimonial = Testimonial::sole();
    expect($testimonial->image)->toStartWith(SiteMedia::DIRECTORY.'/testimonials/');
    Storage::disk('media-test')->assertExists(SiteMedia::shotVariant($testimonial->image));

    $this->actingAs(User::factory()->create());
    $this->get('/admin/testimonials/create')->assertForbidden();
});
