<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\Welcome;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        // NOT-1: a welcome email once a parent or academic student verifies their email.
        Event::listen(function (Verified $event) {
            if ($event->user instanceof User && in_array($event->user->role, [Role::Parent, Role::AcademicStudent], true)) {
                $event->user->notify(new Welcome);
            }
        });

        // ACA-4: order files may be up to 25 MB (Livewire's default is 12 MB).
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.config('academic.max_upload_kb')]]);
    }

    /**
     * Public forms are rate-limited per IP address.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('leads', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
