<?php

namespace App\Modules\Orders\Enums;

enum OrderStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
