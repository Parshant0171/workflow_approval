<?php

namespace Jgu\Wfa\Facades;

use Illuminate\Support\Facades\Facade;

class Wfa extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'wfa';
    }
}
