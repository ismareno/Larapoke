<?php

namespace DarkGhostHunter\Larapoke;

use DarkGhostHunter\Larapoke\Blade\LarapokeDirective;
use DarkGhostHunter\Larapoke\Http\Middleware\LarapokeMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class LarapokeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/larapoke.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'larapoke');

        $this->publishes([
            __DIR__.'/../config/larapoke.php' => config_path('larapoke.php'),
        ], 'config');

        $this->bootMiddleware();

        $this->bootBladeDirective();
    }

    /**
     * Registers (or push globally) the Middleware
     *
     * @return void
     */
    protected function bootMiddleware()
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');

        $router->aliasMiddleware('larapoke', LarapokeMiddleware::class);

        // If Larapoke is set to auto, push the middleware as global.
        if ($this->app->make('config')['larapoke.mode'] === 'auto') {
            $this->app->make(Kernel::class)->pushMiddleware(LarapokeMiddleware::class);
        }
    }

    /**
     * Registers the Blade Directive
     *
     * @return void
     */
    protected function bootBladeDirective()
    {
        /** @var \Illuminate\View\Factory $view */
        $view = $this->app->make('view');

        $view->getEngineResolver()
            ->resolve('blade')
            ->getCompiler()
            ->directive('larapoke', new LarapokeDirective($this->app->make('config'), $view));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larapoke.php', 'larapoke');
    }
}