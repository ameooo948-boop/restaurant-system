<?php

namespace App\Modules\Payments\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case VodafoneCash = 'vodafone_cash';
}
