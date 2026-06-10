<?php

namespace App\Enums;

enum CopyStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case BORROWED = 'BORROWED';
    case RESERVED = 'RESERVED';
    case LOST = 'LOST';
}