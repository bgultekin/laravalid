<?php namespace Bllim\Laravalid;

use Illuminate\Support\ServiceProvider;

/**
 * @property \Illuminate\Container\Container $app
 */
class LaravalidServiceProvider extends ServiceProvider {

	/**
	 * {@inheritdoc}
	 */
	protected $defer = false;

	/**
	 * {@inheritdoc}
	 */
	public function boot()
	{
		$this->package('bllim/laravalid', 'laravalid');

		// register routes for `remote` validations
		$app = $this->app;
		$routeName = $app['config']->get('laravalid::route', 'laravalid');

		$app['router']->any($routeName . '/{rule}', function ($rule) use ($app) {
			return $app['laravalid']->converter()->route()->convert($rule, array($app['request']->all()));
		})->where('rule', '[\w-]+');
	}

	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		// try to register the HTML builder instance
		if (!$this->app->bound('html')) {
			$this->app->singleton('html', $this->app->share(function($app)
			{
				return new \Illuminate\Html\HtmlBuilder($app['url']);
			}));
		}

		// register the new form builder instance
		$this->app->singleton('laravalid', $this->app->share(function ($app) {
			/* @var $app \Illuminate\Container\Container */
			$plugin = $app['config']->get('laravalid::plugin');
			$converterClass = (strpos($plugin, '\\') === false ? 'Bllim\Laravalid\Converter\\' : '') . $plugin . '\Converter';

			/* @var $session \Illuminate\Session\Store */
			$session = $app['session.store'];
			$form = new FormBuilder($app['html'], $app['url'], $session->getToken(), new $converterClass($app));

			return $form->setSessionStore($session);
		}));
	}

	/**
	 * {@inheritdoc}
	 */
	public function provides()
	{
		return array('laravalid');
	}

}
