<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A student whose record is Inactive, Dropped or Unofficially Dropped may
 * still log in, but only reaches their dashboard, where a pop-up that cannot
 * be closed explains their status (partials/status-lock). Every other page
 * sends them back there, and the only thing they can do is log out.
 */
class RestrictInactiveStudent
{
    private const ALLOWED_ROUTES = ['student.dashboard', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStudent() || ! $user->student || $user->student->isActive()) {
            return $next($request);
        }

        if ($request->routeIs(self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        // The QR scanner posts with fetch and expects JSON back
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Your student record is ' . $user->student->status . '. Please contact the registrar.',
            ], 403);
        }

        return redirect()->route('student.dashboard');
    }
}
