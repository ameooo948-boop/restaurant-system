<?php

namespace App\Modules\Kitchen\Enums;

enum KitchenTicketStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Ready = 'ready';
}
