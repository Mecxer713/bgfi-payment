<?php

namespace Mecxer713\BgfiPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BgfiCallbackReceived
{
    use Dispatchable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}