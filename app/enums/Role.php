<?php

namespace App\Enums;

enum Role: string
{
    case MEMBER = 'MEMBER';
    case LIBRARIAN = 'LIBRARIAN';
    case ADMIN = 'ADMIN';
}