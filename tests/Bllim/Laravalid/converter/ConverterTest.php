<?php namespace Bllim\Laravalid\Converter;

use Bllim\Laravalid\FormBuilderTest;

class ConverterTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Container\Container
	 */
	protected $app;

	/**
	 * @var JqueryValidation\Converter
	 */
	protected $converter;

	/**
	 * @param \PHPUnit_Framework_TestCase $test
	 * @param bool $trans Mock Translator::get() method?
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Container\Container
	 */
	static function initApplicationMock(\PHPUnit_Framework_TestCase $test, $trans = false)
	{
		$config = $test->getMock('Illuminate\Config\Repository', array('get'), array(), '', false);
		$config->expects($test->any())->method('get')->willReturnCallback(function ($key, $default = null) {
			return isset($default) ? $default : ($key == 'laravalid::plugin' ? 'JqueryValidation' : null);
		});

		$url = $test->getMock('Illuminate\Routing\UrlGenerator', array('to'), array(), '', false);
		$url->expects($test->any())->method('to')->willReturnCallback(function ($path) {
			return '/' . ltrim($path, '/');
		});

		$encrypter = $test->getMock('Illuminate\Encryption\Encrypter', array('encrypt'), array(), '', false);
		$encrypter->expects($test->any())->method('encrypt')->willReturnCallback(function ($data) {
			return str_replace(array('/', '+', '='), array('_', '-', ''), base64_encode($data));
		});

		$loader = $test->getMock('Illuminate\Translation\LoaderInterface');
		$loader->expects($test->any())->method('load')->with('en', 'validation', '*')->willReturn(static::$messages);
		//
		$translator = $test->getMock('Illuminate\Translation\Translator', !$trans ? array('has') : array('has', 'get'), array($loader, 'en'));
		$translator->expects($test->any())->method('has')->willReturn(false);

		$app = $test->getMock('Illuminate\Container\Container', array('make'));// Illuminate\Foundation\Application
		$app->expects($test->any())->method('make')->willReturnMap(array(
			array('config', array(), $config),
			array('url', array(), $url),
			array('encrypter', array(), $encrypter),
			array('translator', array(), $translator),
		));

		return $app;
	}

	protected function setUp()
	{
		parent::setUp();

		$this->app = $this->initApplicationMock($this, $this->getName(false) == 'testDefaultErrorMessage');
		$this->converter = new JqueryValidation\Converter($this->app);
	}

	static function invokeMethod($object, $methodName, $parameters = array())
	{
		$method = new \ReflectionMethod(get_class($object), $methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, is_array($parameters) ? $parameters : array_slice(func_get_args(), 2));
	}

	public function testConstructor()
	{
		$this->assertInstanceOf(__NAMESPACE__ . '\JqueryValidation\Rule', $this->converter->rule());
		$this->assertInstanceOf(__NAMESPACE__ . '\JqueryValidation\Message', $this->converter->message());
		$this->assertInstanceOf(__NAMESPACE__ . '\JqueryValidation\Route', $this->converter->route());

		$this->assertAttributeEquals($this->converter->rule(), 'rule', $this->converter);
		$this->assertAttributeEquals($this->converter->message(), 'message', $this->converter);
		$this->assertAttributeEquals($this->converter->route(), 'route', $this->converter);
	}

	public function testSetRules()
	{
		$this->converter->reset();
		$this->assertEquals(array(), $this->converter->getValidationRules());

		$this->converter->set($rules = array('foo_bar' => 'required|email'));
		$this->assertEquals($rules, $this->converter->getValidationRules());

		$this->converter->set(null);
		$this->assertEquals($rules, $this->converter->getValidationRules());

		$value = $this->invokeMethod($this->converter, 'getValidationRule', 'foo_bar');
		$this->assertEquals(array('required', 'email'), $value);

		$this->converter->set('foo_bar');
		$this->assertEquals(array('foo_bar'), $this->converter->getValidationRules());

		$this->assertTrue($this->invokeMethod($this->converter, 'checkValidationRule', 0));
		$this->assertFalse($this->invokeMethod($this->converter, 'checkValidationRule', 'foo'));
	}

	/**
	 * @param string $rule
	 * @param array $expected
	 * @dataProvider dataForParseValidationRule
	 */
	public function testParseValidationRule($rule = '', $expected = array())
	{
		$value = $this->invokeMethod($this->converter, 'parseValidationRule', $rule);
		$this->assertEquals($expected, $value);
	}

	public function dataForParseValidationRule()
	{
		return array(
			array('', array('name' => '', 'parameters' => array())),
			array('required', array('name' => 'required', 'parameters' => array())),
			array('min:3', array('name' => 'min', 'parameters' => array('3'))),
			array('exists:users,uid', array('name' => 'exists', 'parameters' => array('users', 'uid'))),
			array('max:255', array('name' => 'max', 'parameters' => array('255'))),
			array('unique:users,email', array('name' => 'unique', 'parameters' => array('users', 'email'))),
			array('regex:/^\d+,$/', array('name' => 'regex', 'parameters' => array('/^\d+,$/'))),
			array('same:pwd,1', array('name' => 'same', 'parameters' => array('pwd,1'))),
			array('required_without:first_name', array('name' => 'required_without', 'parameters' => array('first_name'))),
			array('required_with:name|max', array('name' => 'required_with', 'parameters' => array('name|max'))),
			array('different:name', array('name' => 'different', 'parameters' => array('name'))),
			array('active_url:anon', array('name' => 'active_url', 'parameters' => array('anon'))),
			array('between:20,30', array('name' => 'between', 'parameters' => array('20', '30'))),
			array('in:US,VN', array('name' => 'in', 'parameters' => array('US', 'VN'))),
			array('after:1900-01-01|before', array('name' => 'after', 'parameters' => array('1900-01-01|before'))),
			array('before:2018-01-01', array('name' => 'before', 'parameters' => array('2018-01-01'))),
			array('array', array('name' => 'array', 'parameters' => array())),
			array('mimes:csv,txt', array('name' => 'mimes', 'parameters' => array('csv', 'txt'))),
		);
	}

	/**
	 * @param array $rules
	 * @param string $expected
	 * @dataProvider dataForGetTypeOfInput
	 */
	public function testGetTypeOfInput($rules = array(), $expected = '')
	{
		$value = $this->invokeMethod($this->converter, 'getTypeOfInput', array($rules));
		$this->assertEquals($expected, $value);
	}

	public function dataForGetTypeOfInput()
	{
		return array(
			array(array(), 'string'),
			array(array('required_with:name', 'numeric'), 'numeric'),
			array(array('min:0', 'integer'), 'numeric'),
			array(array('between:0,5', 'array'), 'array'),
			array(array('before:2018-01-01', 'image'), 'file'),
			array(array('max:100', 'mimes'), 'file'),
			array(array('required', 'unique', 'after:1900-01-01'), 'string'),
		);
	}

	/**
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForDefaultErrorMessage
	 */
	public function testDefaultErrorMessage($params = array(), $expected = array())
	{
		$this->app['translator']->expects($this->exactly(2))->method('get')->willReturnCallback(function ($key, $data = array()) {
			return $key . (empty($data) ? '' : json_encode($data));
		});

		$value = $this->invokeMethod($this->converter, 'getDefaultErrorMessage', $params);
		$this->assertEquals($expected, $value);
	}

	public function dataForDefaultErrorMessage()
	{
		return array(
			array(array('url', 'barFoo'), array('data-msg-url' => 'validation.url{"attribute":"bar foo"}')),
			array(array('date', 'bar_Foo'), array('data-msg-date' => 'validation.date{"attribute":"bar  foo"}')),
			array(array('email', 'Foo'), array('data-msg-email' => 'validation.email{"attribute":"foo"}')),
		);
	}

	/**
	 * @param string $inputName
	 * @param string $inputType
	 * @param array $expected
	 * @dataProvider dataForTestConvert
	 */
	public function testConvert($inputName = '', $inputType = null, $expected = array())
	{
		$this->converter->set(FormBuilderTest::$validationRules);

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
			array('', null, array()),
			array('not_exists', null, array()),
			//
			array('uid', 'text', array(
				'required' => 'required', 'data-msg-required' => 'The UID field is required.',
				'minlength' => '3', 'data-msg-minlength' => 'The UID must be at least {0} characters.',
				'maxlength' => '30', 'data-msg-maxlength' => 'The UID may not be greater than {0} characters.',
				'pattern' => '^[A-Za-z0-9_.-]+$', 'data-msg-pattern' => 'The UID may only contain letters and numbers.',
				'data-rule-remote' => '/laravalid/exists?params=dXNlcnMsdWlk', 'data-msg-remote' => 'The UID did not exist.',
			)),
			array('email', 'email', array(
				'required' => 'required', 'data-msg-required' => 'The email field is required.',
				'maxlength' => '255', 'data-msg-maxlength' => 'The email may not be greater than {0} characters.',
				'data-msg-email' => 'The email must be a valid email address.',
				'data-rule-remote' => '/laravalid/unique?params=dXNlcnMsZW1haWw', 'data-msg-remote' => 'The email has already been taken.',
			)),
			array('url', 'url', array(
				'required' => 'required', 'data-msg-required' => 'The URL field is required.',
				'maxlength' => '255', 'data-msg-maxlength' => 'The URL may not be greater than {0} characters.',
				'data-msg-url' => 'The URL format is invalid.',
				'data-rule-remote' => '/laravalid/unique-active_url?params[]=dXNlcnMsdXJs&params[]=',
			)),
			array('name', 'text', array(
				'maxlength' => '255', 'data-msg-maxlength' => 'The name may not be greater than {0} characters.',
				'pattern' => '^[A-Za-z_.-]+$', 'data-msg-pattern' => 'The name may only contain letters.',
			)),
			//
			array('pwd', 'password', array(
				'minlength' => '6', 'data-msg-minlength' => 'The password must be at least {0} characters.',
				'maxlength' => '15', 'data-msg-maxlength' => 'The password may not be greater than {0} characters.',
				'pattern' => '^[0-9]+[xX][0-9]+$', 'data-msg-pattern' => 'The password format is invalid.',
			)),
			array('confirm_pwd', 'password', array(
				'minlength' => '6', 'data-msg-minlength' => 'The confirmation must be at least {0} characters.',
				'maxlength' => '15', 'data-msg-maxlength' => 'The confirmation may not be greater than {0} characters.',
				'data-rule-equalto' => ':input[name=\'pwd\']', 'data-msg-equalto' => 'The confirmation and password must match.',
			)),
			//
			array('first_name', 'text', array(
				'data-rule-required' => ':input:enabled[name=\'name\']:not(:checkbox):not(:radio):filled,input:enabled[name=\'name\']:checked',
				'data-msg-required' => 'The first name field is required when name is present.',
				'maxlength' => '100', 'data-msg-maxlength' => 'The first name may not be greater than {0} characters.',
				'data-rule-notequalto' => ':input[name=\'name\']', 'data-msg-notequalto' => 'The first name and name must be different.',
			)),
			array('last_name', 'text', array(
				'data-rule-required' => ':input:enabled[name=\'first_name\']:not(:checkbox):not(:radio):blank,input:enabled[name=\'first_name\']:unchecked',
				'data-msg-required' => 'The last name field is required when first name is not present.',
				'maxlength' => '100', 'data-msg-maxlength' => 'The last name may not be greater than {0} characters.',
			)),
			//
			array('photo', 'url', array(
				'maxlength' => '1000', 'data-msg-maxlength' => 'The photo may not be greater than {0} characters.',
				'data-msg-url' => 'The photo format is invalid.',
				'data-rule-remote' => '/laravalid/active_url?params=YW5vbg', 'data-msg-remote' => 'The photo is not a valid URL.',
			)),
			array('gender', null, array()),// unsupported: boolean
			array('birthdate', 'date', array(
				'data-msg-date' => 'The birthdate is not a valid date.',
				'min' => '1900-01-01', 'data-msg-min' => 'The birthdate must be a date after {0}.',
				'max' => '2018-01-01', 'data-msg-max' => 'The birthdate must be a date before {0}.',
			)),
			array('phone', 'text', array(
				'data-rule-rangelength' => '20,30', 'data-msg-rangelength' => 'The phone must be between {0} and {1} characters.',
				'maxlength' => '30',
			)),
			array('country', null, array()),// unsupported: in
			//
			array('rating', 'number', array(
				'data-msg-number' => 'The rating must be a number.',
				'data-rule-range' => '0,100', 'data-msg-range' => 'The rating must be between {0} and {1}.',
			)),
			array('duration', 'number', array(
				'data-rule-integer' => 'true', 'data-msg-integer' => 'The length must be an integer.',
				'min' => '0', 'data-msg-min' => 'The length must be at least {0}.',
				'max' => '18000', 'data-msg-max' => 'The length may not be greater than {0}.',
			)),
			//
			array('description', null, array(
				'maxlength' => '2000', 'data-msg-maxlength' => 'The description may not be greater than {0} characters.',
			)),
			array('roles', 'checkbox', array(
				'minlength' => '1', 'data-msg-minlength' => 'The roles must have at least {0} items.',
				'maxlength' => '3', 'data-msg-maxlength' => 'The roles may not have more than {0} items.',
			)),
			//
			array('avatar', 'file', array(
				'accept' => 'image/*', 'data-msg-accept' => 'The avatar must be an image.',
				'minlength' => '30', 'data-msg-minlength' => 'The avatar must be at least {0} kilobytes.',
				'maxlength' => '300', 'data-msg-maxlength' => 'The avatar may not be greater than {0} kilobytes.',
			)),
			array('settings', null, array(
				'data-rule-rangelength' => '0,5', 'data-msg-rangelength' => 'The settings must have between {0} and {1} items.',
				'maxlength' => '5',
			)),
			array('client_ip', 'text', array(
				'data-rule-ipv4' => 'true', 'data-msg-ipv4' => 'The client ip must be a valid IP address.',
			)),
			array('upload', 'file', array(
				'accept' => '.csv,.txt', 'data-msg-accept' => 'The upload must be a file of type: csv, txt.',
				'data-rule-rangelength' => '100,500', 'data-msg-rangelength' => 'The upload must be between {0} and {1} kilobytes.',
				'maxlength' => '500',
			)),
		);
	}
}
