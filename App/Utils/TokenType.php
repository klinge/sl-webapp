<?php

declare(strict_types=1);

namespace App\Utils;

//Used mainly in AuthController
enum TokenType: string
{
    case ACTIVATION = 'activate';
    case RESET = 'reset';
}
