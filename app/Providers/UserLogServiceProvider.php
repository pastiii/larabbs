<?php

namespace App\Providers;

use App\Services\UserLogService;
use Illuminate\Support\ServiceProvider;

/**
 * Class UserServiceProvider
 * @package App\Providers
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/17
 * Time: 16:12
 */
class UserLogServiceProvider extends ServiceProvider
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

    //使用singleton绑定单例
    public function register()
    {
        $this->app->singleton('UserLogin',function(){
            return new UserLogService();
        });
    }
}
