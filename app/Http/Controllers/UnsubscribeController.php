<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * NOT-3: one click on the signed link in a marketing email (or the mail
 * app's own unsubscribe button) stops further marketing email.
 */
class UnsubscribeController extends Controller
{
    public function __invoke(Request $request, string $type, int $id): View|Response
    {
        if ($type === 'user') {
            User::query()->whereKey($id)->update(['marketing_opt_in_at' => null]);
        } else {
            Lead::query()->whereKey($id)->update(['marketing_consent' => false]);
        }

        return $request->isMethod('post') ? response()->noContent() : view('site.unsubscribed');
    }
}
