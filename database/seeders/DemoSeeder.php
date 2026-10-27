<?php

namespace Database\Seeders;

use App\Actions\Batches\ScheduleSessions;
use App\Enums\ConsentPurpose;
use App\Enums\EnrolmentStatus;
use App\Enums\Offering;
use App\Enums\Role;
use App\Models\Batch;
use App\Models\Consent;
use App\Models\Enrolment;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Made-up people and batches for trying the portal on a laptop and for the
 * browser tests: `php artisan db:seed --class=DemoSeeder`. Safe to run
 * again. Never runs in production. Every password is "password".
 */
class DemoSeeder extends Seeder
{
    public function run(ScheduleSessions $schedule): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The demo data is for laptops only.');
        }

        $teacherUser = $this->user('teacher@example.com', 'Meera Iyer (demo)', Role::Teacher);
        $teacher = Teacher::query()->whereBelongsTo($teacherUser)->first()
            ?? Teacher::query()->make(['offering' => Offering::Tuition, 'subjects' => ['Maths', 'Science']])->user()->associate($teacherUser);
        $teacher->save();

        $parentUser = $this->user('parent@example.com', 'Asha Verma (demo)', Role::Parent);
        $parent = ParentProfile::query()->whereBelongsTo($parentUser)->first()
            ?? ParentProfile::query()->make(['mobile' => '9876543210'])->user()->associate($parentUser);
        $parent->save();

        $childUser = User::query()->where('username', 'riyademo')->first() ?? new User(['name' => 'Riya', 'password' => 'password']);
        $childUser->forceFill(['role' => Role::Student, 'username' => 'riyademo'])->save();
        $child = Student::query()->whereBelongsTo($childUser)->first()
            ?? $parent->students()->make(['first_name' => 'Riya', 'class_level' => 10, 'board' => 'CBSE'])->user()->associate($childUser);
        $child->save();
        if (! $child->hasActiveConsent()) {
            Consent::record($parentUser, ConsentPurpose::ChildData, $child);
        }

        $maths = $this->batch($teacher, 'Maths', [1, 3, 5], '17:00', 1500);
        $this->batch($teacher, 'Science', [2, 4], '18:00', 1800);

        $enrolment = Enrolment::query()->whereBelongsTo($child)->whereBelongsTo($maths)->first()
            ?? (new Enrolment)->forceFill(['student_id' => $child->id, 'batch_id' => $maths->id]);
        $enrolment->forceFill(['status' => EnrolmentStatus::Active, 'paid_through' => Enrolment::today()->addDays(20)->toDateString()])->save();

        // A Maths class running now, so Join can be tried straight away.
        $now = $maths->sessions()->whereNull('scheduled_for')->first() ?? $maths->sessions()->make();
        $now->forceFill(['starts_at' => now()->subMinutes(5), 'ends_at' => now()->addMinutes(55), 'status' => 'scheduled'])->save();

        Batch::query()->each(fn (Batch $batch) => $schedule->handle($batch));

        $this->command->info('Demo logins (password "password"): parent@example.com, teacher@example.com, student username riyademo.');
    }

    private function user(string $email, string $name, Role $role): User
    {
        $user = User::query()->where('email', $email)->first() ?? new User(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->forceFill(['role' => $role, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        return $user;
    }

    /**
     * @param  list<int>  $days
     */
    private function batch(Teacher $teacher, string $subject, array $days, string $start, int $feeRupees): Batch
    {
        $batch = Batch::query()->where(['subject' => $subject, 'class_level' => 10, 'board' => 'CBSE'])->first() ?? new Batch;
        $batch->fill([
            'subject' => $subject,
            'class_level' => 10,
            'board' => 'CBSE',
            'teacher_id' => $teacher->id,
            'schedule' => array_map(fn (int $day) => ['day' => $day, 'start' => $start, 'minutes' => 60], $days),
            'monthly_fee' => Money::fromRupees($feeRupees),
            'meet_link' => 'https://meet.google.com/new',
        ])->save();

        return $batch;
    }
}
