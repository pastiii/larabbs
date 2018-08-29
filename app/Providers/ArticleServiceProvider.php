<?php

namespace App\Providers;

use App\Services\ArticleService;
use Illuminate\Support\ServiceProvider;

/**
 * Class ArticleServiceProvider
 * @package App\Providers
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/30
 * Time: 11:18
 */
class ArticleServiceProvider extends ServiceProvider
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
        $this->app->singleton('article',function(){
            return new ArticleService();
        });
    }
}
