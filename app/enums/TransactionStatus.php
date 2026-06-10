<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case RETURNED = 'RETURNED';
    case OVERDUE = 'OVERDUE';
}