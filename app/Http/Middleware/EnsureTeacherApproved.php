<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HIR-8: a teacher who signed up themselves sees only the "waiting for
 * approval" home page until the admin approves them.
 */
class EnsureTeacherApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->teacher?->isApproved() !== true) {
            return redirect()->route('teacher.home');
        }

        return $next($request);
    }
}
