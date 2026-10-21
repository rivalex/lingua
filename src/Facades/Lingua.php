<?php

namespace Rivalex\Lingua\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Rivalex\Lingua\Lingua
 */
class Lingua extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rivalex\Lingua\Lingua::class;
    }
}
