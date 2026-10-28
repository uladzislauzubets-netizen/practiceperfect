<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ImpersonationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $impersonating = $request->hasSession() && $request->session()->has(ImpersonationManager::SESSION_KEY);

        if ($user !== null && $user->must_change_password && ! $impersonating) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
