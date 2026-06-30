<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces an authenticated user who has not completed onboarding to the onboarding page.
 * The onboarding routes themselves are exempt so the redirect cannot loop.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->onboarded && ! $request->routeIs('admin.onboarding*')) {
            return redirect()->route('admin.onboarding');
        }

        return $next($request);
    }
}
