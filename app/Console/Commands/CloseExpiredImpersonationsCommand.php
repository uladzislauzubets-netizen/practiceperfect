<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ImpersonationLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('impersonation:close-expired')]
#[Description('Close impersonation sessions that ran past their one hour limit')]
class CloseExpiredImpersonationsCommand extends Command
{
    public function handle(): int
    {
        $closed = 0;

        ImpersonationLog::query()
            ->whereNull('ended_at')
            ->where('expires_at', '<=', now())
            ->each(function (ImpersonationLog $log) use (&$closed) {
                $log->update([
                    'ended_at' => $log->expires_at,
                    'duration_seconds' => max(0, (int) $log->started_at->diffInSeconds($log->expires_at, true)),
                ]);
                $closed++;
            });

        $this->info("Closed {$closed} session(s).");

        return self::SUCCESS;
    }
}
