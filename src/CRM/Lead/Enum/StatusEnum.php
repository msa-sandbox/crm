<?php

namespace App\CRM\Lead\Enum;

enum StatusEnum: string
{
    case ACTIVE = 'active';
    case WON = 'won';
    case LOST = 'lost';
    case ARCHIVED = 'archived';
}
