<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectChildAccounts
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isChildAccount(), 403, 'Ask your parent to do this for you.');

        return $next($request);
    }
}
