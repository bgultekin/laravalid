<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

class RouteTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var \Mockery\MockInterface|Factory
	 */
	protected $validator;

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
		$this->encrypter = \Mockery::mock(Encrypter::class);
		$this->route = new JqueryValidation\Route($this->validator, $this->encrypter);
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

		$response = $this->route->convert('active_url', [['Bar' => 'foo', '_' => time()]]);
		$this->assertEquals(JsonResponse::class, get_class($response));
		$this->assertEquals($msg, $response->getData());
	}

	public function testExists()
	{
		$this->encrypter->shouldReceive('decrypt')->once()->andReturnUsing(function ($data) {
			return substr($data, 1);
		});

		$this->validator->shouldReceive('make')->with(['foo' => 'Bar'], ['foo' => ['exists:Tbl,field,ID']])->once()
			->andReturn($validator = \Mockery::mock(Validator::class));
		$validator->shouldReceive('fails')->once()->andReturn(false);

		$response = $this->route->convert('exists', [['foo' => 'Bar', 'params' => '~Tbl,field,ID', '_' => time()]]);
		$this->assertEquals(JsonResponse::class, get_class($response));
		$this->assertTrue($response->getData());
	}

	public function testUnique()
	{
		$this->encrypter->shouldReceive('decrypt')->times(2)->andReturnUsing(function ($data) {
			return empty($data) ? $data : substr($data, 1);
		});

		$this->validator->shouldReceive('make')->with(['Foo' => 'Bar'], ['Foo' => ['unique:Tbl,field,Id,#Bar', 'active_url:anon']])->once()
			->andReturn($validator = \Mockery::mock(Validator::class));
		$validator->shouldReceive('fails')->once()->andReturn(false);

		$response = $this->route->convert('unique-active_url', [['Foo' => 'Bar', 'params' => ['~Tbl,field,Id,#Bar', '+anon'], '_' => time()]]);
		$this->assertEquals(JsonResponse::class, get_class($response));
		$this->assertTrue($response->getData());
	}

	public function testExtend()
	{
		$this->route->extend('unique', function ($parameters = []) {
			return $parameters;
		});
		$this->assertEquals($params = ['foo' => '?', 'params' => '~'], $this->route->convert('Unique', [$params]));
	}

}
