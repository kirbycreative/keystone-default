<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePageTreeApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->onboardingState()->contentUnlocked()) {
            return redirect()
                ->route('admin.dashboard')
                ->withErrors(['build' => 'Approve the page tree before opening the Content workspace.']);
        }

        return $next($request);
    }
}
