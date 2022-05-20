<?php

namespace Borntobeyours\Ovo;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Borntobeyours\Ovo\Skeleton\SkeletonClass
 */
class OvoFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ovo';
    }
}
