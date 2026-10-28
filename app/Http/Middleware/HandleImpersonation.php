<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ImpersonationAction;
use App\Services\ImpersonationManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    public function __construct(private readonly ImpersonationManager $impersonation) {}

    public function handle(Request $request, Closure $next): Response
    {
        $log = $this->impersonation->activeLog();

        if ($log === null) {
            return $next($request);
        }

        if ($this->impersonation->isExpired($log)) {
            $this->impersonation->stop(expired: true);

            return redirect()->route('admin.users.index')->with('error', 'The impersonation session expired after one hour.');
        }

        $response = $next($request);

        if (! $request->isMethodSafe() && ! $request->routeIs('impersonation.stop')) {
            ImpersonationAction::query()->create([
                'impersonation_log_id' => $log->id,
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'status_code' => $response->getStatusCode(),
                'created_at' => now(),
            ]);
        }

        return $response;
    }
}
