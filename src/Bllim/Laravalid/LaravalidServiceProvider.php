<?php

namespace Bllim\Laravalid;

use Collective\Html\HtmlBuilder;
use Illuminate\Support\ServiceProvider;

class LaravalidServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        $this->publishes([
            __DIR__ . '/../../../config/config.php' => $app['path.config'] . '/laravalid.php',
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../../public' => $app['path.public'] . '/vendor/laravalid',
        ], 'public');

        // register routes for `remote` validations
        $routeName = $app['config']['laravalid.route'];

        $app['router']->any($routeName . '/{rule}', function ($rule) use ($app) {
            return $app['laravalid']->converter()->route()->convert($rule, [$app['request']->all()]);
        })->where('rule', '[\w-]+');
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/config.php', 'laravalid'
        );

        if (!isset($this->app['html'])) {
            $this->app->singleton('html', function ($app) {
                return new HtmlBuilder($app['url'], $app['view']);
            });
        }

        $this->app->singleton('laravalid', function ($app) {
            $plugin = $app['config']['laravalid.plugin'];
            $converterClass = (strpos($plugin, '\\') === false ? 'Bllim\Laravalid\Converter\\' : '') . $plugin . '\Converter';

            /* @var $session \Illuminate\Session\Store */
            $session = $app['session.store'];
            $form = new FormBuilder($app['html'], $app['url'], $app['view'], $session->token(), new $converterClass($app), $app['request']);

            return $form->setSessionStore($session);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return ['laravalid'];
    }
}
