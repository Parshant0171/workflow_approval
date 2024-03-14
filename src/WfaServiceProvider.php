<?php

namespace Jgu\Wfa;

use Illuminate\Support\ServiceProvider;

class WfaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'jgu');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'jgu');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
        $this->publishes([
            __DIR__.'/../config/wfa.php' => config_path('wfa.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wfa.php', 'wfa');        

        // Register the service the package provides.
        $this->app->singleton('wfa', function ($app) {
            return new Wfa;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['wfa'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/wfa.php' => config_path('wfa.php'),
        ], 'wfa.config');        

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/jgu'),
        ], 'wfa.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/jgu'),
        ], 'wfa.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/jgu'),
        ], 'wfa.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
