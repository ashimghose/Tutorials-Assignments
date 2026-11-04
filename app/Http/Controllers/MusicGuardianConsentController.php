<?php

namespace App\Http\Controllers;

use App\Enums\ConsentPurpose;
use App\Models\Consent;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MUS-3: a parent approves an under-18 music learner's account from the
 * signed link in their email. No parent login is needed.
 */
class MusicGuardianConsentController extends Controller
{
    public function show(Student $student): View
    {
        abort_unless($student->isMinorMusicLearner() && $student->user_id !== null, 404);

        return view('site.music.guardian-consent', [
            'learner' => $student->load('user'),
            'approved' => $student->guardian_consented_at !== null,
        ]);
    }

    public function accept(Request $request, Student $student): View
    {
        abort_unless($student->isMinorMusicLearner(), 404);
        $learner = $student->user()->firstOrFail();

        $request->validate([
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => __('Please tick the box to confirm you are the parent or guardian.')]);

        if ($student->guardian_consented_at === null) {
            DB::transaction(function () use ($student, $learner) {
                $student->forceFill(['guardian_consented_at' => now()])->save();
                Consent::record($learner, ConsentPurpose::MusicGuardian, $student);
            });
        }

        return view('site.music.guardian-consent', ['learner' => $student->load('user'), 'approved' => true]);
    }
}
