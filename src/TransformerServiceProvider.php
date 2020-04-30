<?php

namespace Cybex\Transformer;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // publish package config to app config space
        $this->publishes([__DIR__.'/../config/transformer.php' => config_path('transformer.php'),]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // merge package configuration file with the application's published copy.
        // This will allow users to define only the options they actually want to override in the published copy of the configuration.
        $this->mergeConfigFrom(__DIR__.'/../config/transformer.php', 'transformer');

        $this->app->singleton("Transformer", function () {
            return new \Cybex\Transformer\Transformer(
                config('transformer.secret',''),
                config('transformer.api_url','https://transformer.goodbaby.eu/api/v1/'),
                config('transformer.delivery_url','https://images.goodbaby.eu/')
            );
        });

        $this->app->alias('Transformer', Transformer::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Transformer::class];
    }
}
