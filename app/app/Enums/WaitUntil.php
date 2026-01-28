<?php

declare(strict_types=1);

namespace App\Enums;

enum WaitUntil: string
{
    case Load = 'load';
    case DomContentLoaded = 'domcontentloaded';
    case NetworkIdle = 'networkidle';
}
