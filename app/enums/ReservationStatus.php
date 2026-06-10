<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'PENDING';
    case FULFILLED = 'FULFILLED';
    case CANCELLED = 'CANCELLED';
}