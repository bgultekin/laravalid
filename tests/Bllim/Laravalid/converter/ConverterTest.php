<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Translation\LoaderInterface;
use Illuminate\Translation\Translator;

class ConverterTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var \Mockery\MockInterface|Container
	 */
	protected $app;

	/**
	 * @var JqueryValidation\Converter
	 */
	protected $converter;

	static function initApplicationMock()
	{
		$config = \Mockery::mock(Repository::class);
		$config->shouldReceive('get')->zeroOrMoreTimes()->andReturnUsing(function ($key, $default = null) {
			return isset($default) ? $default : 'JqueryValidation';
		});

		$url = \Mockery::mock(UrlGenerator::class);
		$url->shouldReceive('to')->andReturnUsing(function ($path) {
			return '/' . ltrim($path, '/');
		});

		/* @var $encrypter \Mockery\MockInterface|Encrypter */
		$encrypter = \Mockery::mock(Encrypter::class);
		$encrypter->shouldReceive('encrypt')->andReturnUsing(function ($data) {
			return str_replace(['/', '+', '='], ['_', '-', ''], base64_encode($data));
		});

		// validation messages
		$loader = \Mockery::mock(LoaderInterface::class);
		$loader->shouldReceive('load')->with('en', 'validation', '*')->andReturn(static::$messages);
		//
		$translator = \Mockery::mock(new Translator($loader, 'en'));
		$translator->shouldReceive('has')->zeroOrMoreTimes()->andReturn(false);

		$mocks = compact('config', 'url', 'encrypter', 'translator');
		/* @var $app \Mockery\MockInterface|Container */
		$app = \Mockery::mock(Container::class);// Illuminate\Foundation\Application

		$app->shouldReceive('make')->andReturnUsing($func = function ($key) use ($mocks) {
			return isset($mocks[$key]) ? $mocks[$key] : null;
		});
		$app->shouldReceive('offsetGet')->zeroOrMoreTimes()->andReturnUsing($func);

		return $app;
	}

	protected function setUp()
	{
		parent::setUp();

		$this->app = $this->initApplicationMock();
		$this->converter = new JqueryValidation\Converter($this->app);
	}

	protected function tearDown()
	{
		parent::tearDown();
		\Mockery::close();
	}

	static function invokeMethod($object, $methodName, $parameters = array())
	{
		$method = new \ReflectionMethod(get_class($object), $methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, is_array($parameters) ? $parameters : array_slice(func_get_args(), 2));
	}

	public function testConstructor()
	{
		$this->assertEquals(JqueryValidation\Rule::class, get_class($this->converter->rule()));
		$this->assertEquals(JqueryValidation\Message::class, get_class($this->converter->message()));
		$this->assertEquals(JqueryValidation\Route::class, get_class($this->converter->route()));

		$this->assertAttributeEquals($this->converter->rule(), 'rule', $this->converter);
		$this->assertAttributeEquals($this->converter->message(), 'message', $this->converter);
		$this->assertAttributeEquals($this->converter->route(), 'route', $this->converter);
	}

	public function testSetRules()
	{
		$this->converter->reset();
		$this->assertEquals([], $this->converter->getValidationRules());

		$this->converter->set($rules = ['foo_bar' => 'required|email']);
		$this->assertEquals($rules, $this->converter->getValidationRules());

		$this->converter->set(null);
		$this->assertEquals($rules, $this->converter->getValidationRules());

		$value = $this->invokeMethod($this->converter, 'getValidationRule', 'foo_bar');
		$this->assertEquals(['required', 'email'], $value);

		$this->converter->set('foo_bar');
		$this->assertEquals(['foo_bar'], $this->converter->getValidationRules());

		$this->assertTrue($this->invokeMethod($this->converter, 'checkValidationRule', 0));
		$this->assertFalse($this->invokeMethod($this->converter, 'checkValidationRule', 'foo'));
	}

	/**
	 * @param string $rule
	 * @param array $expected
	 * @dataProvider dataForParseValidationRule
	 */
	public function testParseValidationRule($rule = '', $expected = [])
	{
		$value = $this->invokeMethod($this->converter, 'parseValidationRule', $rule);
		$this->assertEquals($expected, $value);
	}

	public function dataForParseValidationRule()
	{
		return array(
			['', ['name' => '', 'parameters' => []]],
			['required', ['name' => 'required', 'parameters' => []]],
			['min:3', ['name' => 'min', 'parameters' => ['3']]],
			['exists:users,uid', ['name' => 'exists', 'parameters' => ['users', 'uid']]],
			['max:255', ['name' => 'max', 'parameters' => ['255']]],
			['unique:users,email', ['name' => 'unique', 'parameters' => ['users', 'email']]],
			['regex:/^\d+,$/', ['name' => 'regex', 'parameters' => ['/^\d+,$/']]],
			['same:pwd,1', ['name' => 'same', 'parameters' => ['pwd,1']]],
			['required_without:first_name', ['name' => 'required_without', 'parameters' => ['first_name']]],
			['required_with:name|max', ['name' => 'required_with', 'parameters' => ['name|max']]],
			['different:name', ['name' => 'different', 'parameters' => ['name']]],
			['active_url:anon', ['name' => 'active_url', 'parameters' => ['anon']]],
			['between:20,30', ['name' => 'between', 'parameters' => ['20', '30']]],
			['in:US,VN', ['name' => 'in', 'parameters' => ['US', 'VN']]],
			['after:1900-01-01|before', ['name' => 'after', 'parameters' => ['1900-01-01|before']]],
			['before:2018-01-01', ['name' => 'before', 'parameters' => ['2018-01-01']]],
			['array', ['name' => 'array', 'parameters' => []]],
			['mimes:csv,txt', ['name' => 'mimes', 'parameters' => ['csv', 'txt']]],
		);
	}

	/**
	 * @param array $rules
	 * @param string $expected
	 * @dataProvider dataForGetTypeOfInput
	 */
	public function testGetTypeOfInput($rules = [], $expected = '')
	{
		$value = $this->invokeMethod($this->converter, 'getTypeOfInput', [$rules]);
		$this->assertEquals($expected, $value);
	}

	public function dataForGetTypeOfInput()
	{
		return array(
			[[], 'string'],
			[['required_with:name', 'numeric'], 'numeric'],
			[['min:0', 'integer'], 'numeric'],
			[['between:0,5', 'array'], 'array'],
			[['before:2018-01-01', 'image'], 'file'],
			[['max:100', 'mimes'], 'file'],
			[['required', 'unique', 'after:1900-01-01'], 'string'],
		);
	}

	/**
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForDefaultErrorMessage
	 */
	public function testDefaultErrorMessage($params = [], $expected = [])
	{
		$this->app['translator']->shouldReceive('get')->times(2)->andReturnUsing(function ($key, $data = []) {
			return $key . (empty($data) ? '' : json_encode($data));
		});

		$value = $this->invokeMethod($this->converter, 'getDefaultErrorMessage', $params);
		$this->assertEquals($expected, $value);
	}

	public function dataForDefaultErrorMessage()
	{
		return array(
			[['url', 'barFoo'], ['data-msg-url' => 'validation.url{"attribute":"bar foo"}']],
			[['date', 'bar_Foo'], ['data-msg-date' => 'validation.date{"attribute":"bar  foo"}']],
			[['email', 'Foo'], ['data-msg-email' => 'validation.email{"attribute":"foo"}']],
		);
	}

	/**
	 * @param string $inputName
	 * @param string $inputType
	 * @param array $expected
	 * @dataProvider dataForTestConvert
	 */
	public function testConvert($inputName = '', $inputType = null, $expected = [])
	{
		$this->converter->set(\Bllim\Laravalid\FormBuilderTest::$validationRules);

		$value = $this->converter->convert($inputName, $inputType);
		$this->assertEquals($expected, $value);
	}

	protected static $messages = array(
		'active_url' => 'The :attribute is not a valid URL.',
		'after' => 'The :attribute must be a date after :date.',
		'alpha' => 'The :attribute may only contain letters.',
		'alpha_num' => 'The :attribute may only contain letters and numbers.',
		'array' => 'The :attribute must be an array.',
		'before' => 'The :attribute must be a date before :date.',
		'between' => array(
			'numeric' => 'The :attribute must be between :min and :max.',
			'file' => 'The :attribute must be between :min and :max kilobytes.',
			'string' => 'The :attribute must be between :min and :max characters.',
			'array' => 'The :attribute must have between :min and :max items.',
		),
		'boolean' => 'The :attribute field must be true or false.',
		'date' => 'The :attribute is not a valid date.',
		'different' => 'The :attribute and :other must be different.',
		'email' => 'The :attribute must be a valid email address.',
		'exists' => 'The :attribute did not exist.',
		'image' => 'The :attribute must be an image.',
		'in' => 'The selected :attribute is invalid.',
		'integer' => 'The :attribute must be an integer.',
		'ip' => 'The :attribute must be a valid IP address.',
		'max' => array(
			'numeric' => 'The :attribute may not be greater than :max.',
			'file' => 'The :attribute may not be greater than :max kilobytes.',
			'string' => 'The :attribute may not be greater than :max characters.',
			'array' => 'The :attribute may not have more than :max items.',
		),
		'mimes' => 'The :attribute must be a file of type: :values.',
		'min' => array(
			'numeric' => 'The :attribute must be at least :min.',
			'file' => 'The :attribute must be at least :min kilobytes.',
			'string' => 'The :attribute must be at least :min characters.',
			'array' => 'The :attribute must have at least :min items.',
		),
		'numeric' => 'The :attribute must be a number.',
		'regex' => 'The :attribute format is invalid.',
		'required' => 'The :attribute field is required.',
		'required_with' => 'The :attribute field is required when :values is present.',
		'required_without' => 'The :attribute field is required when :values is not present.',
		'same' => 'The :attribute and :other must match.',
		'unique' => 'The :attribute has already been taken.',
		'url' => 'The :attribute format is invalid.',
		//
		'attributes' => array(
			'uid' => 'UID',
			'duration' => 'length',
			'url' => 'URL',
			'pwd' => 'password',
			'confirm_pwd' => 'confirmation',
		),
	);

	public function dataForTestConvert()
	{
		return array(
			['', null, []],
			['not_exists', null, []],
			//
			['uid', 'text', [
				'required' => 'required', 'data-msg-required' => 'The UID field is required.',
				'minlength' => '3', 'data-msg-minlength' => 'The UID must be at least {0} characters.',
				'maxlength' => '30', 'data-msg-maxlength' => 'The UID may not be greater than {0} characters.',
				'pattern' => '^[A-Za-z0-9_.-]+$', 'data-msg-pattern' => 'The UID may only contain letters and numbers.',
				'data-rule-remote' => '/laravalid/exists?params=dXNlcnMsdWlk', 'data-msg-remote' => 'The UID did not exist.',
			]],
			['email', 'email', [
				'required' => 'required', 'data-msg-required' => 'The email field is required.',
				'maxlength' => '255', 'data-msg-maxlength' => 'The email may not be greater than {0} characters.',
				'data-msg-email' => 'The email must be a valid email address.',
				'data-rule-remote' => '/laravalid/unique?params=dXNlcnMsZW1haWw', 'data-msg-remote' => 'The email has already been taken.',
			]],
			['url', 'url', [
				'required' => 'required', 'data-msg-required' => 'The URL field is required.',
				'maxlength' => '255', 'data-msg-maxlength' => 'The URL may not be greater than {0} characters.',
				'data-msg-url' => 'The URL format is invalid.',
				'data-rule-remote' => '/laravalid/unique-active_url?params[]=dXNlcnMsdXJs&params[]=',
			]],
			['name', 'text', [
				'maxlength' => '255', 'data-msg-maxlength' => 'The name may not be greater than {0} characters.',
				'pattern' => '^[A-Za-z_.-]+$', 'data-msg-pattern' => 'The name may only contain letters.',
			]],
			//
			['pwd', 'password', [
				'minlength' => '6', 'data-msg-minlength' => 'The password must be at least {0} characters.',
				'maxlength' => '15', 'data-msg-maxlength' => 'The password may not be greater than {0} characters.',
				'pattern' => '^[0-9]+[xX][0-9]+$', 'data-msg-pattern' => 'The password format is invalid.',
			]],
			['confirm_pwd', 'password', [
				'minlength' => '6', 'data-msg-minlength' => 'The confirmation must be at least {0} characters.',
				'maxlength' => '15', 'data-msg-maxlength' => 'The confirmation may not be greater than {0} characters.',
				'data-rule-equalto' => ':input[name=\'pwd\']', 'data-msg-equalto' => 'The confirmation and password must match.',
			]],
			//
			['first_name', 'text', [
				'data-rule-required' => ':input:enabled[name=\'name\']:not(:checkbox):not(:radio):filled,input:enabled[name=\'name\']:checked',
				'data-msg-required' => 'The first name field is required when name is present.',
				'maxlength' => '100', 'data-msg-maxlength' => 'The first name may not be greater than {0} characters.',
				'data-rule-notequalto' => ':input[name=\'name\']', 'data-msg-notequalto' => 'The first name and name must be different.',
			]],
			['last_name', 'text', [
				'data-rule-required' => ':input:enabled[name=\'first_name\']:not(:checkbox):not(:radio):blank,input:enabled[name=\'first_name\']:unchecked',
				'data-msg-required' => 'The last name field is required when first name is not present.',
				'maxlength' => '100', 'data-msg-maxlength' => 'The last name may not be greater than {0} characters.',
			]],
			//
			['photo', 'url', [
				'maxlength' => '1000', 'data-msg-maxlength' => 'The photo may not be greater than {0} characters.',
				'data-msg-url' => 'The photo format is invalid.',
				'data-rule-remote' => '/laravalid/active_url?params=YW5vbg', 'data-msg-remote' => 'The photo is not a valid URL.',
			]],
			['gender', null, []],// unsupport: boolean
			['birthdate', 'date', [
				'data-msg-date' => 'The birthdate is not a valid date.',
				'min' => '1900-01-01', 'data-msg-min' => 'The birthdate must be a date after {0}.',
				'max' => '2018-01-01', 'data-msg-max' => 'The birthdate must be a date before {0}.',
			]],
			['phone', 'text', [
				'data-rule-rangelength' => '20,30', 'data-msg-rangelength' => 'The phone must be between {0} and {1} characters.',
				'maxlength' => '30',
			]],
			['country', null, []],// unsupport: in
			//
			['rating', 'number', [
				'data-msg-number' => 'The rating must be a number.',
				'data-rule-range' => '0,100', 'data-msg-range' => 'The rating must be between {0} and {1}.',
			]],
			['duration', 'number', [
				'data-rule-integer' => 'true', 'data-msg-integer' => 'The length must be an integer.',
				'min' => '0', 'data-msg-min' => 'The length must be at least {0}.',
				'max' => '18000', 'data-msg-max' => 'The length may not be greater than {0}.',
			]],
			//
			['description', null, [
				'maxlength' => '2000', 'data-msg-maxlength' => 'The description may not be greater than {0} characters.',
			]],
			['roles', 'checkbox', [
				'minlength' => '1', 'data-msg-minlength' => 'The roles must have at least {0} items.',
				'maxlength' => '3', 'data-msg-maxlength' => 'The roles may not have more than {0} items.',
			]],
			//
			['avatar', 'file', [
				'accept' => 'image/*', 'data-msg-accept' => 'The avatar must be an image.',
				'minlength' => '30', 'data-msg-minlength' => 'The avatar must be at least {0} kilobytes.',
				'maxlength' => '300', 'data-msg-maxlength' => 'The avatar may not be greater than {0} kilobytes.',
			]],
			['settings', null, [
				'data-rule-rangelength' => '0,5', 'data-msg-rangelength' => 'The settings must have between {0} and {1} items.',
				'maxlength' => '5',
			]],
			['client_ip', 'text', [
				'data-rule-ipv4' => 'true', 'data-msg-ipv4' => 'The client ip must be a valid IP address.',
			]],
			['upload', 'file', [
				'accept' => '.csv,.txt', 'data-msg-accept' => 'The upload must be a file of type: csv, txt.',
				'data-rule-rangelength' => '100,500', 'data-msg-rangelength' => 'The upload must be between {0} and {1} kilobytes.',
				'maxlength' => '500',
			]],
		);
	}

}
