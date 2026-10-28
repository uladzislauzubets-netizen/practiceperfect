<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('approvals:expire')->everyFiveMinutes();
Schedule::command('impersonation:close-expired')->everyFiveMinutes();
