<?php namespace Bllim\Laravalid;

use Illuminate\Support\ServiceProvider;

class LaravalidServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../../../config' => config_path('laravalid'),
		], 'config');

		$this->publishes([
		    __DIR__.'/../../../public' => public_path('laravalid'),
		], 'public');

		$routeName = \Config::get('laravalid.route');

		// remote validations
		\Route::any($routeName.'/{rule}', '\Bllim\Laravalid\RuleController@getIndex');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerResources();
        
        if(!isset($this->app['html']))
        {
			$this->app->singleton('html', function($app)
			{
				return new \Collective\Html\HtmlBuilder($app['url']);
			});
        }

        $this->app->singleton('laravalid', function ($app) {
            	$plugin = \Config::get('laravalid.plugin');
            	$converterClassName = 'Bllim\Laravalid\Converter\\'.$plugin.'\Converter';
            	$converter = new $converterClassName();

				$form = new FormBuilder($app->make('html'), $app->make('url'), $app->make('session.store')->getToken(), $converter);
				return $form->setSessionStore($app->make('session.store'));
            }
        );
	}

	/**
	 * Register the package resources.
	 *
	 * @return void
	 */
	protected function registerResources()
	{
	    $userConfigFile    = app()->configPath().'/laravalid/config.php';
	    $packageConfigFile = __DIR__.'/../../../config/config.php';
	    $config            = $this->app['files']->getRequire($packageConfigFile);

	    if (file_exists($userConfigFile)) {
	        $userConfig = $this->app['files']->getRequire($userConfigFile);
	        $config     = array_replace_recursive($config, $userConfig);
	    }

	    $this->app['config']->set('laravalid', $config);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
