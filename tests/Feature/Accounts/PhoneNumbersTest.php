<?php

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\DB;

function signUp(array $overrides = []): array
{
    return [
        'account_type' => 'parent',
        'name' => 'Asha Verma',
        'email' => 'asha@example.com',
        'mobile_country' => 'IN',
        'mobile' => '98765 43210',
        'password' => 'password',
        'password_confirmation' => 'password',
        'consent' => '1',
        ...$overrides,
    ];
}

test('AUTH-1 a parent in the UAE signs up with a UAE mobile', function () {
    $this->post(route('register.store'), signUp(['mobile_country' => 'AE', 'mobile' => '050 123 4567']))
        ->assertSessionHasNoErrors();

    expect(User::sole()->parentProfile->mobile)->toBe('+971501234567');
});

test('AUTH-1 a mobile that does not fit the country chosen is refused', function () {
    $this->post(route('register.store'), signUp(['mobile_country' => 'AE', 'mobile' => '98765 43210']))
        ->assertSessionHasErrors(['mobile' => 'Enter a valid mobile number for the country you chose.']);

    expect(User::count())->toBe(0);
});

test('AUTH-1 an unknown country code is refused', function () {
    $this->post(route('register.store'), signUp(['mobile_country' => 'ZZ']))
        ->assertSessionHasErrors(['mobile_country']);
});

test('the sign-up form has the country dropdown and the same checks as the server', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('data-phone-field', false)
        ->assertSee('name="mobile_country" value="IN"', false)
        ->assertSee('data-phone="mobile_country"', false)
        ->assertSee('data-msg-phone="Enter a valid mobile number for the country you chose."', false)
        ->assertSee('data-msg-required="The mobile field is required."', false)
        ->assertSee('data-msg-required="Please agree to the privacy policy to create an account."', false)
        ->assertSee('data-checks novalidate', false);
});

test('a sign-up refused by the server keeps the country picked', function () {
    $this->from(route('register'))
        ->post(route('register.store'), signUp(['mobile_country' => 'BD', 'mobile' => '123', 'consent' => null]))
        ->assertRedirect(route('register'));

    $this->get(route('register'))->assertSee('name="mobile_country" value="BD"', false)->assertSee('+880');
});

test('CMS-3 an enquiry from Bangladesh keeps its country code', function () {
    $this->seed(ContentSeeder::class);
    config(['features.cms' => true]);

    $this->get(route('enquire.show', 'tuition'))->assertOk()->assertSee('data-phone-field', false)->assertSee('data-checks novalidate', false);

    $this->post(route('enquire.store', 'tuition'), [
        'name' => 'Rahim',
        'mobile_country' => 'BD',
        'mobile' => '01712 345678',
        'section' => 'tuition',
    ])->assertSessionHasNoErrors();

    expect(Lead::sole()->mobile)->toBe('+8801712345678');
});

test('numbers saved before country codes get +91', function () {
    $lead = Lead::factory()->create();
    DB::table('leads')->where('id', $lead->id)->update(['mobile' => '9876543210']);

    (require database_path('migrations/2026_11_05_000002_store_mobiles_with_country_code.php'))->up();

    expect($lead->fresh()->mobile)->toBe('+919876543210');
});
