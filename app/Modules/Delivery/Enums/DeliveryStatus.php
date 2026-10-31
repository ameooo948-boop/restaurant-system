<?php

namespace App\Modules\Delivery\Enums;

enum DeliveryStatus: string
{
    case PendingAssignment = 'pending_assignment';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
