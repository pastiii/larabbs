<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Overtrue\EasySms\EasySms;

/**
 * Class UserServiceProvider
 * @package App\Providers
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/23
 * Time: 16:12
 */
class EasySmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EasySms::class, function ($app) {
            return new EasySms(config('easysms'));
        });

        $this->app->alias(EasySms::class, 'easysms');
    }
}
