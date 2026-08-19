<?php

namespace App\Enums;

enum RegistrationFeePaidVia: string
{
    case Cashfree = 'cashfree';
    /** @deprecated Legacy gateway; retained so historical payment rows still cast cleanly. */
    case Razorpay = 'razorpay';
    case ActivationCode = 'activation_code';
}
