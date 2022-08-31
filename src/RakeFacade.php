<?php

namespace Uocnv\Rake;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Uocnv\Rake\Skeleton\SkeletonClass
 */
class RakeFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'rake';
    }
}
