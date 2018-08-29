<?php
/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/7/13
 * Time: 17:48
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MessageTemplateService;

class MessageTemplateServiceProvider extends ServiceProvider
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
        $this->app->singleton('message_template', function () {
            return new MessageTemplateService();
        });
    }
}