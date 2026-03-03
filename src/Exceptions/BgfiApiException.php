<?php

namespace Mecxer713\BgfiPayment\Exceptions;

use Exception;

class BgfiApiException extends Exception
{
    public static function authFailed($message, $status): self
    {
        return new static("BGFI authentication failed ($status): $message");
    }
}
