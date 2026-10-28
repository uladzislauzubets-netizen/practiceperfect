<?php

declare(strict_types=1);

namespace App\Enums;

enum ActiveStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
