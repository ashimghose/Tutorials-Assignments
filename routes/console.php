<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// M5: keep four weeks of classes on the timetable (CLS-2), and send fee
// reminders and pause unpaid enrolments (PAY-4). Times are India time.
Schedule::command('app:schedule-classes')->dailyAt('05:30')->timezone(config('app.display_timezone'));
Schedule::command('app:check-enrolments')->dailyAt('09:00')->timezone(config('app.display_timezone'));

// M6: delete order files after the retention period (privacy).
Schedule::command('app:prune-order-files')->dailyAt('03:00')->timezone(config('app.display_timezone'));
