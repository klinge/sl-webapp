<?php

namespace App\Utils;

enum EmailType: string
{
        //The value must correspond to the name of the template file in views/emails
    case VERIFICATION = 'verification';
    case VERIFICATION_SUCCESS = 'verification_success';
    case PASSWORD_RESET = 'password_reset';
    case PASSWORD_RESET_SUCCESS = 'password_reset_success';
    case WELCOME = 'welcome';
    case TEST = 'test';
}
