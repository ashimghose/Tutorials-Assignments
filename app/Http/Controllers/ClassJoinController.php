<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * CLS-2, CLS-3: the Join button. The Meet link is never shown on the page;
 * each click is checked here first.
 */
class ClassJoinController extends Controller
{
    public function __invoke(Request $request, ClassSession $classSession): RedirectResponse
    {
        Gate::authorize('join', $classSession);

        $link = $classSession->meetLink();
        abort_if($link === null, 404, __('The class link is not ready yet. Please message us.'));

        // The teacher joining marks the class as started (for payouts, ADM-2).
        if ($classSession->started_at === null && $classSession->batch->teacher?->user_id === $request->user()->id) {
            $classSession->forceFill(['started_at' => now()])->save();
        }

        return redirect()->away($link);
    }
}
