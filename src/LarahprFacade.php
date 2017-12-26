<?php

namespace Sungmee\Larahpr;

use Illuminate\Support\Facades\Facade;

class LarahprFacade extends Facade {
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'HP';
    }
}