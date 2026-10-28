<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\PaymentType;
use App\Models\ChildPurchaseApproval;

final class AmountFormatter
{
    public static function format(ChildPurchaseApproval $approval): string
    {
        return $approval->payment_type === PaymentType::Usd
            ? '$'.number_format($approval->amount / 100, 2)
            : number_format($approval->amount).' tokens';
    }
}
