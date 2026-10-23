<?php

namespace App\Support;

class WhatsAppContentMessage
{
    /**
     * @param  array<string, string>  $variables
     */
    public function __construct(
        public readonly string $contentSid,
        public readonly array $variables = [],
    ) {}
}
