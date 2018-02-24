<?php namespace Bllim\Laravalid\Converter;

class RuleTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Encryption\Encrypter
	 */
	protected $encrypter;

	/**
	 * @var JqueryValidation\Rule
	 */
	protected $rule;

	protected function setUp()
	{
		parent::setUp();

		$this->encrypter = $this->getMock('Illuminate\Encryption\Encrypter', array('encrypt'), array(), '', false);
		$this->rule = new JqueryValidation\Rule(static::routeUrl(), $this->encrypter);
	}

	protected static function routeUrl()
	{
		static $url;
		if (!isset($url))
			$url = array_rand(array_flip(array('http://localhost:8000/laravalid', '/laravalid')));
		return $url;
	}

	public function testExtend()
	{
		$this->assertNull($this->rule->convert('foo'));

		$this->rule->extend('foo', $func = function () {
			return func_get_args();
		});
		$this->assertEquals($params = array('Bar', array('zoo' => 'foo')), $this->rule->convert('Foo', $params));

		$this->rule->extend('ip', $func);
		$this->assertEquals($params = array(array('name' => 'Ip'), 'barFoo', 'string'), $this->rule->convert('IP', $params));
	}

	/**
	 * @param string $name
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForTestGenericRules
	 */
	public function testGenericRules($name = '', $params = array(), $expected = array())
	{
		$value = call_user_func_array(array($this->rule, strtolower($name)), $params);
		$this->assertEquals($expected, $value);

		$this->assertEquals($expected, $this->rule->convert($name, $params));
	}

	public function dataForTestGenericRules()
	{
		return array(
			array('Email', array(array(), 'foo', 'string'), array('data-rule-email' => 'true')),
			array('required', array(array(), 'bar'), array('required' => 'required')),
			array('Url', array(array(), 'barFoo'), array('data-rule-url' => 'true')),
			array('integer', array(array(), 'bar_foo', 'numeric'), array('data-rule-integer' => 'true')),
			array('Numeric', array(array(), 'bar', 'numeric'), array('data-rule-number' => 'true')),
			array('Ip', array(array(), 'barFoo', 'string'), array('data-rule-ipv4' => 'true')),
			array('Same', array(array('parameters' => array('foo2')), 'bar'), array('data-rule-equalto' => ':input[name=\'foo2\']')),
			array('different', array(array('parameters' => array('foo2')), 'bar'), array('data-rule-notequalto' => ':input[name=\'foo2\']')),
			//
			array('Regex', array(array('parameters' => array('/^\d+$/')), 'bar', 'string'), array('pattern' => '^\d+$')),
			array('regex', array(array('parameters' => array('^\w+$')), 'bar', 'string'), array('pattern' => '^\w+$')),
			array('Alpha', array(array(), 'foo', 'string'), array('pattern' => '^[A-Za-z_.-]+$')),
			array('alpha_Num', array(array(), 'foo', 'string'), array('pattern' => '^[A-Za-z0-9_.-]+$')),
			//
			array('Image', array(array(), 'img', 'file'), array('accept' => 'image/*')),
			array('Date', array(array(), 'bar'), array('data-rule-date' => 'true')),
			array('before', array(array('parameters' => array('1900-01-01')), 'dt'), array('max' => '1900-01-01')),
			array('After', array(array('parameters' => array('2018-01-01')), 'dt'), array('min' => '2018-01-01')),
			//
			array('min', array(array('parameters' => array('1')), 'x', 'numeric'), array('min' => '1')),
			array('Min', array(array('parameters' => array('3')), 'x', 'string'), array('minlength' => '3')),
			array('min', array(array('parameters' => array('100')), 'x', 'file'), array('minlength' => '100')),// KB
			array('max', array(array('parameters' => array('10')), 'x', 'numeric'), array('max' => '10')),
			array('Max', array(array('parameters' => array('255')), 'x', 'string'), array('maxlength' => '255')),
			array('max', array(array('parameters' => array('200')), 'x', 'file'), array('maxlength' => '200')),// KB
			array('Between', array(array('parameters' => array('1', '10')), 'x', 'numeric'), array('data-rule-range' => '1,10')),
			array('between', array(array('parameters' => array('1', '100')), 'x', 'string'), array('data-rule-rangelength' => '1,100', 'maxlength' => '100')),
			//
			array(
				'required_With',
				array(array('parameters' => array('f1', 'f2')), 'foo'),
				array('data-rule-required' => ':input:enabled[name=\'f1\']:not(:checkbox):not(:radio):filled,:input:enabled[name=\'f2\']:not(:checkbox):not(:radio):filled,input:enabled[name=\'f1\']:checked,input:enabled[name=\'f2\']:checked')
			),
			array(
				'Required_withOut',
				array(array('parameters' => array('f2')), 'foo'),
				array('data-rule-required' => ':input:enabled[name=\'f2\']:not(:checkbox):not(:radio):blank,input:enabled[name=\'f2\']:unchecked')
			),
			array('Mimes', array(array('parameters' => array('csv', 'xls')), 'x', 'file'), array('accept' => '.csv,.xls')),
		);
	}

	/**
	 * @param string $name
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForTestRemoteRules
	 */
	public function testRemoteRules($name = '', $params = array(), $expected = array())
	{
		$this->encrypter->expects($this->any())->method('encrypt')->willReturnCallback(function ($data) {
			return '~' . $data;
		});
		$value = call_user_func_array(array($this->rule, strtolower($name)), $params);
		$this->assertEquals($expected, $value);

		$this->assertEquals($expected, $this->rule->convert($name, $params));
	}

	public function dataForTestRemoteRules()
	{
		return array(
			array('Unique', array(array('parameters' => array('Tbl,f,Id,NULL')), 'foo'), array('data-rule-remote' => static::routeUrl() . '/unique?params=~Tbl,f,Id,NULL')),
			array('exists', array(array('parameters' => array('Tbl,f')), 'foo'), array('data-rule-remote' => static::routeUrl() . '/exists?params=~Tbl,f')),
			array('active_Url', array(array('parameters' => array('anon')), 'bar'), array('data-rule-remote' => static::routeUrl() . '/active_url?params=~anon')),
			array('Active_url', array(array('parameters' => array()), 'bar'), array('data-rule-remote' => static::routeUrl() . '/active_url?params=')),
		);
	}

	/**
	 * @param array $outputAttributes
	 * @param array $ruleAttributes
	 * @param string $inputType
	 * @param array $expectedOutput
	 * @param array $expectedRule
	 * @dataProvider dataForMergeOutputAttributes
	 */
	public function testMergeOutputAttributes($outputAttributes = array(), $ruleAttributes = array(), $inputType = null, $expectedOutput = array(), $expectedRule = array())
	{
		$outputAttributes = $this->rule->mergeOutputAttributes($outputAttributes, $ruleAttributes, $inputType);

		$this->assertEquals($expectedOutput, $outputAttributes);
		$this->assertEquals($expectedRule, $ruleAttributes);
	}

	public function dataForMergeOutputAttributes()
	{
		return array(
			array(array(), array(), null, array(), array()),
			array(array(), array('required'), 'text', array('required'), array('required')),
			array(array('pattern' => '*'), array('required'), '', array('pattern' => '*', 'required'), array('required')),
			array(array('pattern' => '*'), array('pattern' => '?'), '', array('pattern' => '*'), array('pattern' => '?')),
			array(array(), array('data-rule-number' => 'true'), 'number', array(), array('data-rule-number' => 'true')),
			array(array('pattern' => '*'), array('data-rule-remote' => '/'), 'email', array('pattern' => '*', 'data-rule-remote' => '/'), array('data-rule-remote' => '/')),
			array(
				array('data-rule-remote' => 'laravalid/active_url'),
				array('data-rule-remote' => '/'),
				'tel',
				array('data-rule-remote' => 'laravalid/active_url'),
				array('data-rule-remote' => '/')
			),
			//
			array(
				array('data-rule-remote' => static::routeUrl() . '/exists?params=pE'),
				array('data-rule-remote' => static::routeUrl() . '/active_url?params='),
				'',
				array('data-rule-remote' => static::routeUrl() . '/exists-active_url?params[]=pE&params[]='),
				array()
			),
			array(
				array('data-rule-remote' => static::routeUrl() . '/unique?params=pU', 'data-msg-remote' => '?'),
				array('data-rule-remote' => static::routeUrl() . '/active_url?params=anon'),
				'text',
				array('data-rule-remote' => static::routeUrl() . '/unique-active_url?params[]=pU&params[]=anon'),
				array()
			),
			array(
				array('data-rule-remote' => static::routeUrl() . '/unique?params=pU', 'data-msg-remote' => '?'),
				array('data-rule-remote' => static::routeUrl() . '/active_url?params=anon', 'data-rule-url' => 'true'),
				'url',
				array('data-rule-remote' => static::routeUrl() . '/unique-active_url?params[]=pU&params[]=anon'),
				array('data-rule-url' => 'true')
			),
			//
			array(
				array('data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists?params=pE","data":{"bar":"foo"}}'),
				array('data-rule-remote' => static::routeUrl() . '/active_url?params='),
				'',
				array('data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists-active_url?params[]=pE&params[]=","data":{"bar":"foo"}}'),
				array()
			),
			array(
				array('data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists?params=pE","data":{"bar":"foo"}}'),
				array('data-rule-remote' => '{"url":"' . static::routeUrl() . '/active_url?params=","data":{"foo":"X"}}'),
				'',
				array('data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists-active_url?params[]=pE&params[]=","data":{"bar":"foo","foo":"X"}}'),
				array()
			),
		);
	}
}
