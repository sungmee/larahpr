<?php

namespace Sungmee\Larahpr;

use Illuminate\Support\Facades\Facade as FacadeParent;

class Facade extends FacadeParent {
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'HPR';
    }
}