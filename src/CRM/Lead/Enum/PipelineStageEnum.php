<?php

namespace App\CRM\Lead\Enum;

enum PipelineStageEnum: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case PROPOSAL = 'proposal';
    case NEGOTIATION = 'negotiation';
    case WON = 'won';
    case LOST = 'lost';
}
