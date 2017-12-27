<?php

namespace Sungmee\Larahpr;

use Illuminate\Support\Facades\Facade;

class HPR extends Facade {
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'HPR';
    }
}