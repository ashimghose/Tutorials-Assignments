<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Razorpay\Api\Api;

test('the R2 disks are private and fail loudly', function (string $disk) {
    $config = config("filesystems.disks.{$disk}");

    expect($config['driver'])->toBe('s3')
        ->and($config['visibility'])->toBe('private')
        ->and($config['use_path_style_endpoint'])->toBeTrue()
        ->and($config['throw'])->toBeTrue();
})->with(['r2', 'r2-backups']);

test('backups are the database only, encrypted, kept 30 days and emailed about', function () {
    expect(config('backup.backup.source.files.include'))->toBe([])
        ->and(config('backup.backup.encryption'))->toBe('default')
        ->and(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(30)
        ->and(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(0)
        ->and(config('backup.notifications.mail.to'))->not->toBe('your@example.com');
});

test('the backup runs nightly in India time, after the clean-up', function () {
    $events = collect(app(Schedule::class)->events())
        ->mapWithKeys(fn (Event $event) => [trim(str($event->command)->after('artisan')->replace(["'", '"'], '')) => $event]);

    expect($events['backup:run --only-db']->expression)->toBe('0 2 * * *')
        ->and($events['backup:run --only-db']->timezone)->toBe('Asia/Kolkata')
        ->and($events['backup:clean']->expression)->toBe('30 1 * * *')
        ->and($events)->toHaveKey('backup:monitor');
});

test('a backup run writes a password-protected zip to the backup disk', function () {
    // Backs up one small file rather than the database, so the test needs no
    // sqlite3 or pg_dump program; the zipping, encryption and upload are the same.
    Storage::fake('backup-test');
    $source = storage_path('framework/testing/backup-source.txt');
    File::ensureDirectoryExists(dirname($source));
    File::put($source, 'hello');
    config([
        'backup.backup.source.files.include' => [$source],
        'backup.backup.destination.disks' => ['backup-test'],
        'backup.backup.password' => 'test-password',
    ]);

    $this->artisan('backup:run', ['--only-files' => true, '--disable-notifications' => true])->assertSuccessful();

    $zips = collect(Storage::disk('backup-test')->allFiles())->filter(fn (string $path) => str_ends_with($path, '.zip'));
    expect($zips)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backup-test')->path($zips->first()));
    $entry = $zip->statIndex(0);
    $zip->close();
    File::delete($source);

    expect($entry['encryption_method'])->not->toBe(ZipArchive::EM_NONE);
});

test('the Razorpay keys are read from the environment, never the code', function () {
    expect(config('services.razorpay'))->toHaveKeys(['key_id', 'key_secret', 'webhook_secret'])
        ->and(class_exists(Api::class))->toBeTrue();
});
