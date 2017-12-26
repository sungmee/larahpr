<?php

namespace Sungmee\Larahpr;

use Illuminate\Support\ServiceProvider;

class LarahprServiceProvider extends ServiceProvider
{
    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/larahpr.php', 'larahpr'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('HP', function () {
            return new Helper;
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['HP'];
    }
}