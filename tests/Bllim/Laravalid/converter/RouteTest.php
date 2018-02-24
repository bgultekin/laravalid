<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Support\MessageBag;

class RouteTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Validation\Factory
	 */
	protected $validator;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Encryption\Encrypter
	 */
	protected $encrypter;

	/**
	 * @var JqueryValidation\Route
	 */
	protected $route;

	protected function setUp()
	{
		parent::setUp();

		$this->validator = $this->getMock('Illuminate\Validation\Factory', array('make'), array(), '', false);
		$this->encrypter = $this->getMock('Illuminate\Encryption\Encrypter', array('decrypt'), array(), '', false);
		$this->route = new JqueryValidation\Route($this->validator, $this->encrypter);
	}

	public function testActiveUrl()
	{
		$validator = $this->getMock('Illuminate\Validation\Validator', array('fails', 'messages'), array(), '', false);
		$this->validator->expects($this->once())->method('make')
			->with(array('Bar' => 'foo'), array('Bar' => array('active_url')))->willReturn($validator);
		$validator->expects($this->once())->method('fails')->willReturn(true);
		$validator->expects($this->once())->method('messages')->willReturn(new MessageBag(array('Bar' => $msg = 'Inactive URL')));

		$response = $this->route->convert('active_url', array(array('Bar' => 'foo', '_' => time())));
		$this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
		$this->assertAttributeEquals('"' . $msg . '"', 'data', $response);
	}

	public function testExists()
	{
		$this->encrypter->expects($this->once())->method('decrypt')->willReturnCallback(function ($data) {
			return substr($data, 1);
		});

		$validator = $this->getMock('Illuminate\Validation\Validator', array('fails'), array(), '', false);
		$this->validator->expects($this->once())->method('make')
			->with(array('foo' => 'Bar'), array('foo' => array('exists:Tbl,field,ID')))->willReturn($validator);
		$validator->expects($this->once())->method('fails')->willReturn(false);

		$response = $this->route->convert('exists', array(array('foo' => 'Bar', 'params' => '~Tbl,field,ID', '_' => time())));
		$this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
		$this->assertAttributeEquals('true', 'data', $response);
	}

	public function testUnique()
	{
		$this->encrypter->expects($this->exactly(2))->method('decrypt')->willReturnCallback(function ($data) {
			return empty($data) ? $data : substr($data, 1);
		});

		$validator = $this->getMock('Illuminate\Validation\Validator', array('fails'), array(), '', false);
		$this->validator->expects($this->once())->method('make')
			->with(array('Foo' => 'Bar'), array('Foo' => array('unique:Tbl,field,Id,#Bar', 'active_url:anon')))->willReturn($validator);
		$validator->expects($this->once())->method('fails')->willReturn(false);

		$response = $this->route->convert('unique-active_url', array(array('Foo' => 'Bar', 'params' => array('~Tbl,field,Id,#Bar', '+anon'), '_' => time())));
		$this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
		$this->assertAttributeEquals('true', 'data', $response);
	}

	public function testExtend()
	{
		$this->route->extend('unique', function ($parameters = array()) {
			return $parameters;
		});
		$this->assertEquals($params = array('foo' => '?', 'params' => '~'), $this->route->convert('Unique', array($params)));
	}
}
