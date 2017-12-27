<?php

namespace Sungmee\Larahpr;

use Illuminate\Support\ServiceProvider as ServiceProviderParent;

class ServiceProvider extends ServiceProviderParent
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
            __DIR__.'/config.php', 'sungmee'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('HPR', function () {
            return new HPR;
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['HPR'];
    }
}