<?php namespace Bllim\Laravalid;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
	public function testDeferred()
	{
		$provider = new LaravalidServiceProvider(null);

		$this->assertAttributeSame(null, 'app', $provider);
		$this->assertFalse($provider->isDeferred());
	}

	public function testProvides()
	{
		$provider = new LaravalidServiceProvider(null);
		$this->assertEquals(array('laravalid'), $provider->provides());
	}

	public function testRegister()
	{
		$app = $this->getMock('Illuminate\Container\Container', array('bound', 'bind', 'make'));

		$app->expects($this->once())->method('bound')->with('html')->willReturn(false);
		$app->expects($this->exactly(2))->method('bind')
			->withConsecutive(
				array('html', $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_CALLABLE), $this->isTrue()),
				array('laravalid', $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_CALLABLE), $this->isTrue())
			)
			->willReturnCallback(function ($abstract, $closure) use ($app, &$html) {
				$object = $closure($app);
				if ($abstract == 'html') $html = $object;

				\PHPUnit_Framework_TestCase::assertNotEmpty($object);
				\PHPUnit_Framework_TestCase::assertContains('\\' . ucfirst($abstract) . '\\', get_class($object));
			});

		$url = $this->getMock('Illuminate\Routing\UrlGenerator', array('to'), array(), '', false);
		$config = $this->getMock('Illuminate\Config\Repository', array('get'), array(), '', false);
		$session = $this->getMock('Illuminate\Session\Store', array('getToken'), array(), '', false);

		$app->expects($this->atLeast(4))->method('make')->withConsecutive(array('url'), array('config'))->willReturnMap(array(
			array('url', array(), $url),
			array('config', array(), $config),
			array('session.store', array(), $session),
			array('html', array(), &$html),
		));

		$config->expects($this->once())->method('get')->with('laravalid::plugin')->willReturn('\MyTestPlugin');
		$session->expects($this->once())->method('getToken')->willReturn(uniqid());

		if (!class_exists('MyTestPlugin\Converter'))
			eval('namespace MyTestPlugin { class Converter extends \\' . __NAMESPACE__ . '\Converter\Base\Converter { public function __construct() { echo __CLASS__; } } }');

		$this->expectOutputString('MyTestPlugin\Converter');
		/** @noinspection PhpParamsInspection */
		$provider = new LaravalidServiceProvider($app);
		$provider->register();
	}

	public function testBoot()
	{
		$app = $this->getMock('Illuminate\Container\Container', array('make'));

		$files = $this->getMock('Illuminate\Filesystem\Filesystem', null);
		$config = $this->getMock('Illuminate\Config\Repository', array('get', 'package'), array(), '', false);
		$router = $this->getMock('Illuminate\Routing\Router', array('any'), array(), '', false);
		$route = $this->getMock('Illuminate\Routing\Route', array('where'), array(), '', false);

		$form = $this->getMock(__NAMESPACE__ . '\FormBuilder', array('converter'), array(), '', false);
		$converter = $this->getMock(__NAMESPACE__ . '\Converter\Base\Converter', array('route'), array(), '', false);
		$valid = $this->getMock(__NAMESPACE__ . '\Converter\Base\Route', array('convert'), array(), '', false);
		$request = $this->getMock('Illuminate\Http\Request', array('all'), array(), '', false);

		$app->expects($this->atLeast(5))->method('make')->withConsecutive(array('files'), array('config'))->willReturnMap(array(
			array('files', array(), $files),
			array('config', array(), $config),
			array('router', array(), $router),
			array('laravalid', array(), $form),
			array('request', array(), $request),
		));

		$path = realpath(__DIR__ . '/../../../src');
		$config->expects($this->once())->method('package')->with('bllim/laravalid', $path . '/config', 'laravalid');
		$config->expects($this->once())->method('get')->with('laravalid::route', 'laravalid')->willReturnArgument(1);

		$router->expects($this->once())->method('any')->willReturnCallback(function ($url, $action) use ($route) {
			\PHPUnit_Framework_TestCase::assertEquals('laravalid/{rule}', $url);
			\PHPUnit_Framework_TestCase::assertEquals('["exists",[{"params":"~"}]]', $action('exists'));
			return $route;
		});
		$route->expects($this->once())->method('where')->with('rule', '[\w-]+');

		$form->expects($this->once())->method('converter')->willReturn($converter);
		$converter->expects($this->once())->method('route')->willReturn($valid);
		$valid->expects($this->once())->method('convert')->willReturnCallback(function () {
			return json_encode(func_get_args());
		});
		$request->expects($this->once())->method('all')->willReturn(array('params' => '~'));

		/** @noinspection PhpParamsInspection */
		$provider = new LaravalidServiceProvider($app);
		$provider->boot();
	}
}
