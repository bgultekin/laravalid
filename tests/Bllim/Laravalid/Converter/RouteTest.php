<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Support\MessageBag;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Contracts\Validation\Factory
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var JqueryValidation\Route
     */
    protected $route;

    protected function setUp()
    {
        parent::setUp();

        $this->validator = $this->getMock('Illuminate\Contracts\Validation\Factory');
        $this->response = $this->getMock('Illuminate\Contracts\Routing\ResponseFactory');
        $this->encrypter = $this->getMock('Illuminate\Contracts\Encryption\Encrypter');

        $this->route = new JqueryValidation\Route($this->validator, $this->response, $this->encrypter);
    }

    public function testActiveUrl()
    {
        $validator = $this->getMock('Illuminate\Validation\Validator', ['fails', 'messages'], [], '', false);
        $this->validator->expects($this->once())->method('make')
            ->with(['Bar' => 'foo'], ['Bar' => ['active_url']])->willReturn($validator);
        $validator->expects($this->once())->method('fails')->willReturn(true);
        $validator->expects($this->once())->method('messages')->willReturn(new MessageBag(['Bar' => $msg = 'Inactive URL']));

        $this->response->expects($this->once())->method('json')->with($msg)->willReturnArgument(0);
        $response = $this->route->convert('active_url', [['Bar' => 'foo', '_' => time()]]);

        $this->assertEquals($msg, $response);
    }

    public function testExists()
    {
        $this->encrypter->expects($this->once())->method('decrypt')->willReturnCallback(function ($data) {
            return substr($data, 1);
        });

        $validator = $this->getMock('Illuminate\Contracts\Validation\Validator');
        $this->validator->expects($this->once())->method('make')
            ->with(['foo' => 'Bar'], ['foo' => ['exists:Tbl,field,ID']])->willReturn($validator);
        $validator->expects($this->once())->method('fails')->willReturn(false);

        $this->response->expects($this->once())->method('json')->with(true)->willReturnArgument(0);
        $response = $this->route->convert('exists', [['foo' => 'Bar', 'params' => '~Tbl,field,ID', '_' => time()]]);

        $this->assertTrue($response);
    }

    public function testUnique()
    {
        $this->encrypter->expects($this->exactly(2))->method('decrypt')->willReturnCallback(function ($data) {
            return empty($data) ? $data : substr($data, 1);
        });

        $validator = $this->getMock('Illuminate\Contracts\Validation\Validator');
        $this->validator->expects($this->once())->method('make')
            ->with(['Foo' => 'Bar'], ['Foo' => ['unique:Tbl,field,Id,#Bar', 'active_url:anon']])->willReturn($validator);
        $validator->expects($this->once())->method('fails')->willReturn(false);

        $this->response->expects($this->once())->method('json')->with(true)->willReturnArgument(0);
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
