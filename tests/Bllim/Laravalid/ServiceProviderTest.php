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
        $this->assertEquals(['laravalid'], $provider->provides());
    }

    public function testRegister()
    {
        $app = $this->getMock('Illuminate\Container\Container', ['offsetExists', 'bind', 'make']);

        $app->expects($this->once())->method('offsetExists')->with('html')->willReturn(false);
        $app->expects($this->exactly(2))->method('bind')
            ->withConsecutive(
                ['html', $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_CALLABLE), $this->isTrue()],
                ['laravalid', $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_CALLABLE), $this->isTrue()]
            )
            ->willReturnCallback(function ($abstract, $closure) use ($app, &$html) {
                $object = $closure($app);
                if ($abstract == 'html') $html = $object;

                static::assertNotEmpty($object);
                static::assertContains('\\' . ucfirst($abstract) . '\\', get_class($object));
            });

        $url = $this->getMock('Illuminate\Routing\UrlGenerator', ['to'], [], '', false);
        $config = $this->getMock('ArrayAccess', ['get', 'set', 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset']);//Illuminate\Config\Repository
        $session = $this->getMock('Illuminate\Session\Store', ['token'], [], '', false);

        $mocks = [
            'config' => $config,
            'url' => $url,
            'session.store' => $session,
            'html' => &$html,
            'view' => $this->getMock('Illuminate\Contracts\View\Factory'),
        ];
        $app->expects($this->atLeast(5))->method('make')->withConsecutive(['config'], ['config'], ['url'])->willReturnCallback(function ($abstract) use ($mocks) {
            return isset($mocks[$abstract]) ? $mocks[$abstract] : null;
        });

        $config->expects($this->once())->method('get')->with('laravalid', [])->willReturnArgument(1);
        $config->expects($this->once())->method('set')
            ->with('laravalid', ['useLaravelMessages' => true, 'plugin' => 'JqueryValidation', 'route' => 'laravalid']);

        $config->expects($this->once())->method('offsetGet')->with('laravalid.plugin')->willReturn('\MyTestPlugin');
        $session->expects($this->once())->method('token')->willReturn(uniqid());

        if (!class_exists('MyTestPlugin\Converter'))
            eval('namespace MyTestPlugin { class Converter extends \\' . __NAMESPACE__ . '\Converter\Base\Converter { public function __construct() { echo __CLASS__; } } }');

        $this->expectOutputString('MyTestPlugin\Converter');

        $provider = new LaravalidServiceProvider($app);
        $provider->register();
    }

    public function testBoot()
    {
        $app = $this->getMock('Illuminate\Container\Container', ['make']);

        $config = $this->getMock('ArrayAccess');//Illuminate\Config\Repository
        $router = $this->getMock('Illuminate\Routing\Router', ['any'], [], '', false);
        $route = $this->getMock('Illuminate\Routing\Route', ['where'], [], '', false);

        $laravalid = $this->getMock(__NAMESPACE__ . '\FormBuilder', ['converter'], [], '', false);
        $converter = $this->getMock(__NAMESPACE__ . '\Converter\Base\Converter', ['route'], [], '', false);
        $vRoute = $this->getMock(__NAMESPACE__ . '\Converter\Base\Route', ['convert'], [], '', false);
        $request = $this->getMock('Illuminate\Http\Request', ['all'], [], '', false);

        $mocks = compact('config', 'router', 'laravalid', 'request');
        $app->expects($this->atLeast(4))->method('make')->withConsecutive(['path.config'], ['path.public'])->willReturnCallback(function ($abstract) use ($mocks) {
            return isset($mocks[$abstract]) ? $mocks[$abstract] : null;
        });

        $config->expects($this->once())->method('offsetGet')->with('laravalid.route')->willReturn('laravalid');

        $router->expects($this->once())->method('any')->willReturnCallback(function ($url, $action) use ($route) {
            static::assertEquals('laravalid/{rule}', $url);
            static::assertEquals('["exists",[{"params":"~"}]]', $action('exists'));
            return $route;
        });
        $route->expects($this->once())->method('where')->with('rule', '[\w-]+');

        $laravalid->expects($this->once())->method('converter')->willReturn($converter);
        $converter->expects($this->once())->method('route')->willReturn($vRoute);
        $vRoute->expects($this->once())->method('convert')->willReturnCallback(function () {
            return json_encode(func_get_args());
        });
        $request->expects($this->once())->method('all')->willReturn(['params' => '~']);

        $provider = new LaravalidServiceProvider($app);
        $provider->boot();

        ($s = DIRECTORY_SEPARATOR) and $path = substr_replace(__DIR__, "{$s}src{$s}", strrpos(__DIR__, "{$s}tests{$s}"), 7);
        $publishes = [
            'config' => [$path . '/../../../config/config.php' => '/laravalid.php'],
            'public' => [$path . '/../../../public' => '/vendor/laravalid'],
        ];
        $this->assertAttributeEquals([get_class($provider) => call_user_func_array('array_merge', $publishes)], 'publishes', 'Illuminate\Support\ServiceProvider');
        $this->assertAttributeEquals($publishes, 'publishGroups', 'Illuminate\Support\ServiceProvider');
    }
}
