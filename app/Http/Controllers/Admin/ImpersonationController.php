<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use App\Services\ImpersonationManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ImpersonationController extends Controller
{
    public function history(): Response
    {
        $logs = ImpersonationLog::query()
            ->with(['admin', 'impersonated'])
            ->withCount('actions')
            ->latest('started_at')
            ->paginate(20)
            ->through(fn (ImpersonationLog $log) => [
                'id' => $log->id,
                'admin' => $log->admin->name,
                'impersonated' => $log->impersonated->name,
                'impersonated_role' => $log->impersonated->role->label(),
                'started_at' => $log->started_at->toIso8601String(),
                'ended_at' => $log->ended_at?->toIso8601String(),
                'duration_seconds' => $log->duration_seconds,
                'actions_count' => $log->actions_count,
                'ip_address' => $log->ip_address,
            ]);

        return Inertia::render('Admin/Impersonation/History', ['logs' => $logs]);
    }

    public function show(ImpersonationLog $log): Response
    {
        $log->load(['admin', 'impersonated']);

        return Inertia::render('Admin/Impersonation/Show', [
            'log' => [
                'id' => $log->id,
                'admin' => $log->admin->name,
                'impersonated' => $log->impersonated->name,
                'started_at' => $log->started_at->toIso8601String(),
                'ended_at' => $log->ended_at?->toIso8601String(),
                'duration_seconds' => $log->duration_seconds,
            ],
            'actions' => $log->actions()->orderBy('id')->get(['id', 'method', 'path', 'status_code', 'created_at'])->map(fn ($action) => [
                'id' => $action->id,
                'method' => $action->method,
                'path' => $action->path,
                'status_code' => $action->status_code,
                'created_at' => $action->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    public function stop(ImpersonationManager $impersonation): RedirectResponse
    {
        $admin = $impersonation->stop();

        abort_if($admin === null, 403, 'You are not impersonating anyone.');

        return redirect()->route('admin.users.index')->with('success', 'You are back in your own account.');
    }
}
