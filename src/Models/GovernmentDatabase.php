<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

enum GovernmentDatabase: string
{
    case SERPRO_DATAVALID = 'SERPRO_DATAVALID';
    case TSE = 'TSE';
    case IDRC = 'IDRC';
}
