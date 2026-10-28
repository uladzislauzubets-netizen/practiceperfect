<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Approvals\ExpirePendingApprovals;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('approvals:expire')]
#[Description('Deny child purchase requests that were not answered within 48 hours')]
class ExpirePendingApprovalsCommand extends Command
{
    public function handle(ExpirePendingApprovals $expire): int
    {
        $this->info("Expired {$expire->handle()} request(s).");

        return self::SUCCESS;
    }
}
