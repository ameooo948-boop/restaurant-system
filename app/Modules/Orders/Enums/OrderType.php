<?php

namespace App\Modules\Orders\Enums;

enum OrderType: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';
    case DineIn = 'dine_in';
}
