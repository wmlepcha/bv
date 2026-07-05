<?php

namespace App\Enums;

enum RegistrationFeePaidVia: string
{
    case Razorpay = 'razorpay';
    case ActivationCode = 'activation_code';
}
