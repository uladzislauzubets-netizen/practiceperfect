<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ImpersonationManager
{
    public const SESSION_KEY = 'impersonation';

    public const DURATION_MINUTES = 60;

    public function __construct(private readonly Request $request) {}

    public function start(User $admin, User $target): ImpersonationLog
    {
        if (! $admin->can('impersonate', $target)) {
            throw new AuthorizationException('You cannot impersonate this user.');
        }

        $log = ImpersonationLog::query()->create([
            'admin_id' => $admin->id,
            'impersonated_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(self::DURATION_MINUTES),
            'ip_address' => $this->request->ip(),
        ]);

        $this->request->session()->put(self::SESSION_KEY, ['log_id' => $log->id, 'admin_id' => $admin->id]);
        Auth::login($target);
        $this->request->session()->regenerate();

        AuditLog::record($admin, 'impersonation.started', $target, ['log_id' => $log->id]);

        return $log;
    }

    public function stop(bool $expired = false): ?User
    {
        $data = $this->request->session()->pull(self::SESSION_KEY);

        if (! is_array($data)) {
            return null;
        }

        $log = ImpersonationLog::query()->find($data['log_id']);
        $admin = User::query()->find($data['admin_id']);

        if ($log !== null && $log->ended_at === null) {
            $endedAt = $expired ? $log->expires_at : now();
            $log->update([
                'ended_at' => $endedAt,
                'duration_seconds' => max(0, (int) $log->started_at->diffInSeconds($endedAt, true)),
            ]);
        }

        if ($admin !== null) {
            Auth::login($admin);
            $this->request->session()->regenerate();
            AuditLog::record($admin, $expired ? 'impersonation.expired' : 'impersonation.ended', null, ['log_id' => $data['log_id']]);
        }

        return $admin;
    }

    public function activeLog(): ?ImpersonationLog
    {
        $data = $this->request->hasSession() ? $this->request->session()->get(self::SESSION_KEY) : null;

        if (! is_array($data)) {
            return null;
        }

        return ImpersonationLog::query()->with(['admin', 'impersonated'])->find($data['log_id']);
    }

    public function isExpired(ImpersonationLog $log): bool
    {
        return $log->expires_at->isPast();
    }
}
