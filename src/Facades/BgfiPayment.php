<?php

namespace Mecxer713\BgfiPayment\Facades;

use Illuminate\Support\Facades\Facade;

class BgfiPayment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bgfi-payment';
    }
}