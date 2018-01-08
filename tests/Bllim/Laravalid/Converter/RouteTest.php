<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Mockery\MockInterface|Factory
     */
    protected $validator;

    /**
     * @var \Mockery\MockInterface|ResponseFactory
     */
    protected $response;

    /**
     * @var \Mockery\MockInterface|Encrypter
     */
    protected $encrypter;

    /**
     * @var JqueryValidation\Route
     */
    protected $route;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = \Mockery::mock(Factory::class);
        $this->response = \Mockery::mock(ResponseFactory::class);
        $this->encrypter = \Mockery::mock(Encrypter::class);

        $this->route = new JqueryValidation\Route($this->validator, $this->response, $this->encrypter);
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }

    public function testActiveUrl()
    {
        $this->validator->shouldReceive('make')->with(['Bar' => 'foo'], ['Bar' => ['active_url']])->once()
            ->andReturn($validator = \Mockery::mock(Validator::class));
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('messages')->once()->andReturn(new MessageBag(['Bar' => $msg = 'Inactive URL']));

        $this->response->shouldReceive('json')->with($msg)->andReturn($msg);
        $response = $this->route->convert('active_url', [['Bar' => 'foo', '_' => time()]]);

        $this->assertEquals($msg, $response);
    }

    public function testExists()
    {
        $this->encrypter->shouldReceive('decrypt')->once()->andReturnUsing(function ($data) {
            return substr($data, 1);
        });

        $this->validator->shouldReceive('make')->with(['foo' => 'Bar'], ['foo' => ['exists:Tbl,field,ID']])->once()
            ->andReturn($validator = \Mockery::mock(Validator::class));
        $validator->shouldReceive('fails')->once()->andReturn(false);

        $this->response->shouldReceive('json')->with(true)->andReturn(true);
        $response = $this->route->convert('exists', [['foo' => 'Bar', 'params' => '~Tbl,field,ID', '_' => time()]]);

        $this->assertTrue($response);
    }

    public function testUnique()
    {
        $this->encrypter->shouldReceive('decrypt')->times(2)->andReturnUsing(function ($data) {
            return empty($data) ? $data : substr($data, 1);
        });

        $this->validator->shouldReceive('make')->with(['Foo' => 'Bar'], ['Foo' => ['unique:Tbl,field,Id,#Bar', 'active_url:anon']])->once()
            ->andReturn($validator = \Mockery::mock(Validator::class));
        $validator->shouldReceive('fails')->once()->andReturn(false);

        $this->response->shouldReceive('json')->with(true)->andReturn(true);
        $response = $this->route->convert('unique-active_url', [['Foo' => 'Bar', 'params' => ['~Tbl,field,Id,#Bar', '+anon'], '_' => time()]]);

        $this->assertTrue($response);
    }

    public function testExtend()
    {
        $this->route->extend('unique', function ($parameters = []) {
            return $parameters;
        });
        $this->assertEquals($params = ['foo' => '?', 'params' => '~'], $this->route->convert('Unique', [$params]));
    }
}
