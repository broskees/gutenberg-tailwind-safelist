<?php

namespace Broskees\GutenbergTwSafelist;

use Roots\Acorn\ServiceProvider;

class GutenbergTwSafelistServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Broskees\GutenbergTwSafelist', function () {
            return new GutenbergTwSafelist($this->app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/tailwind.php' => $this->app->configPath('tailwind.php'),
        ], 'config');

        $this->commands([
            \Broskees\GutenbergTwSafelist\TailwindUpdateDbCommand::class,
        ]);

        // load helpers
        $this->loadHelpers();

        $this->app->make('Broskees\GutenbergTwSafelist');
    }

    private function loadHelpers()
    {
        require_once dirname(__DIR__) . '/src/helpers.php';
    }
}
