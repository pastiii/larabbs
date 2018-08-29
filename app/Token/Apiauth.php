<?php

namespace App\Token;

/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18/4/19
 * Time: 下午2:46
 */
use Illuminate\Support\Facades\Facade;


class Apiauth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Token';
    }
}