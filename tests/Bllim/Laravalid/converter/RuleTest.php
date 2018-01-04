<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Encryption\Encrypter;

class RuleTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var \Mockery\MockInterface|Encrypter
	 */
	protected $encrypter;

	/**
	 * @var JqueryValidation\Rule
	 */
	protected $rule;

	protected function setUp()
	{
		parent::setUp();

		$this->encrypter = \Mockery::mock(Encrypter::class);
		$this->rule = new JqueryValidation\Rule(static::routeUrl(), $this->encrypter);
	}

	protected function tearDown()
	{
		parent::tearDown();
		\Mockery::close();
	}

	protected static function routeUrl()
	{
		static $url;
		if (!isset($url))
			$url = ['http://localhost:8000/laravalid', '/laravalid'][mt_rand(0, 1)];
		return $url;
	}

	public function testExtend()
	{
		$this->assertNull($this->rule->convert('foo'));

		$this->rule->extend('foo', $func = function () {
			return func_get_args();
		});
		$this->assertEquals($params = ['Bar', ['zoo' => 'foo']], $this->rule->convert('Foo', $params));

		$this->rule->extend('ip', $func);
		$this->assertEquals($params = [['name' => 'Ip'], 'barFoo', 'string'], $this->rule->convert('IP', $params));
	}

	/**
	 * @param string $name
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForTestGenericRules
	 */
	public function testGenericRules($name = '', $params = [], $expected = [])
	{
		$value = call_user_func_array([$this->rule, strtolower($name)], $params);
		$this->assertEquals($expected, $value);

		$this->assertEquals($expected, $this->rule->convert($name, $params));
	}

	public function dataForTestGenericRules()
	{
		return array(
			['Email', [[], 'foo', 'string'], ['data-rule-email' => 'true']],
			['required', [[], 'bar'], ['required' => 'required']],
			['Url', [[], 'barFoo'], ['data-rule-url' => 'true']],
			['integer', [[], 'bar_foo', 'numeric'], ['data-rule-integer' => 'true']],
			['Numeric', [[], 'bar', 'numeric'], ['data-rule-number' => 'true']],
			['Ip', [[], 'barFoo', 'string'], ['data-rule-ipv4' => 'true']],
			['Same', [['parameters' => ['foo2']], 'bar'], ['data-rule-equalto' => ':input[name=\'foo2\']']],
			['different', [['parameters' => ['foo2']], 'bar'], ['data-rule-notequalto' => ':input[name=\'foo2\']']],
			//
			['Regex', [['parameters' => ['/^\d+$/']], 'bar', 'string'], ['pattern' => '^\d+$']],
			['regex', [['parameters' => ['^\w+$']], 'bar', 'string'], ['pattern' => '^\w+$']],
			['Alpha', [[], 'foo', 'string'], ['pattern' => '^[A-Za-z_.-]+$']],
			['alpha_Num', [[], 'foo', 'string'], ['pattern' => '^[A-Za-z0-9_.-]+$']],
			//
			['Image', [[], 'img', 'file'], ['accept' => 'image/*']],
			['Date', [[], 'bar'], ['data-rule-date' => 'true']],
			['before', [['parameters' => ['1900-01-01']], 'dt'], ['max' => '1900-01-01']],
			['After', [['parameters' => ['2018-01-01']], 'dt'], ['min' => '2018-01-01']],
			//
			['min', [['parameters' => ['1']], 'x', 'numeric'], ['min' => '1']],
			['Min', [['parameters' => ['3']], 'x', 'string'], ['minlength' => '3']],
			['min', [['parameters' => ['100']], 'x', 'file'], ['minlength' => '100']],// KB
			['max', [['parameters' => ['10']], 'x', 'numeric'], ['max' => '10']],
			['Max', [['parameters' => ['255']], 'x', 'string'], ['maxlength' => '255']],
			['max', [['parameters' => ['200']], 'x', 'file'], ['maxlength' => '200']],// KB
			['Between', [['parameters' => ['1', '10']], 'x', 'numeric'], ['data-rule-range' => '1,10']],
			['between', [['parameters' => ['1', '100']], 'x', 'string'], ['data-rule-rangelength' => '1,100', 'maxlength' => '100']],
			//
			[
				'required_With',
				[['parameters' => ['f1', 'f2']], 'foo'],
				['data-rule-required' => ':input:enabled[name=\'f1\']:not(:checkbox):not(:radio):filled,:input:enabled[name=\'f2\']:not(:checkbox):not(:radio):filled,input:enabled[name=\'f1\']:checked,input:enabled[name=\'f2\']:checked']
			],
			[
				'Required_withOut',
				[['parameters' => ['f2']], 'foo'],
				['data-rule-required' => ':input:enabled[name=\'f2\']:not(:checkbox):not(:radio):blank,input:enabled[name=\'f2\']:unchecked']
			],
			['Mimes', [['parameters' => ['csv', 'xls']], 'x', 'file'], ['accept' => '.csv,.xls']],
		);
	}

	/**
	 * @param string $name
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForTestRemoteRules
	 */
	public function testRemoteRules($name = '', $params = [], $expected = [])
	{
		$this->encrypter->shouldReceive('encrypt')->andReturnUsing(function ($data) {
			return '~' . $data;
		});
		$value = call_user_func_array([$this->rule, strtolower($name)], $params);
		$this->assertEquals($expected, $value);

		$this->assertEquals($expected, $this->rule->convert($name, $params));
	}

	public function dataForTestRemoteRules()
	{
		return array(
			['Unique', [['parameters' => ['Tbl,f,Id,NULL']], 'foo'], ['data-rule-remote' => static::routeUrl() . '/unique?params=~Tbl,f,Id,NULL']],
			['exists', [['parameters' => ['Tbl,f']], 'foo'], ['data-rule-remote' => static::routeUrl() . '/exists?params=~Tbl,f']],
			['active_Url', [['parameters' => ['anon']], 'bar'], ['data-rule-remote' => static::routeUrl() . '/active_url?params=~anon']],
			['Active_url', [['parameters' => []], 'bar'], ['data-rule-remote' => static::routeUrl() . '/active_url?params=']],
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
	public function testMergeOutputAttributes($outputAttributes = [], $ruleAttributes = [], $inputType = null, $expectedOutput = [], $expectedRule = [])
	{
		$outputAttributes = $this->rule->mergeOutputAttributes($outputAttributes, $ruleAttributes, $inputType);

		$this->assertEquals($expectedOutput, $outputAttributes);
		$this->assertEquals($expectedRule, $ruleAttributes);
	}

	public function dataForMergeOutputAttributes()
	{
		return array(
			[[], [], null, [], []],
			[[], ['required'], 'text', ['required'], ['required']],
			[['pattern' => '*'], ['required'], '', ['pattern' => '*', 'required'], ['required']],
			[['pattern' => '*'], ['pattern' => '?'], '', ['pattern' => '*'], ['pattern' => '?']],
			[[], ['data-rule-number' => 'true'], 'number', [], ['data-rule-number' => 'true']],
			[['pattern' => '*'], ['data-rule-remote' => '/'], 'email', ['pattern' => '*', 'data-rule-remote' => '/'], ['data-rule-remote' => '/']],
			[
				['data-rule-remote' => 'laravalid/active_url'],
				['data-rule-remote' => '/'],
				'tel',
				['data-rule-remote' => 'laravalid/active_url'],
				['data-rule-remote' => '/']
			],
			//
			[
				['data-rule-remote' => static::routeUrl() . '/exists?params=pE'],
				['data-rule-remote' => static::routeUrl() . '/active_url?params='],
				'',
				['data-rule-remote' => static::routeUrl() . '/exists-active_url?params[]=pE&params[]='],
				[]
			],
			[
				['data-rule-remote' => static::routeUrl() . '/unique?params=pU', 'data-msg-remote' => '?'],
				['data-rule-remote' => static::routeUrl() . '/active_url?params=anon'],
				'text',
				['data-rule-remote' => static::routeUrl() . '/unique-active_url?params[]=pU&params[]=anon'],
				[]
			],
			[
				['data-rule-remote' => static::routeUrl() . '/unique?params=pU', 'data-msg-remote' => '?'],
				['data-rule-remote' => static::routeUrl() . '/active_url?params=anon', 'data-rule-url' => 'true'],
				'url',
				['data-rule-remote' => static::routeUrl() . '/unique-active_url?params[]=pU&params[]=anon'],
				['data-rule-url' => 'true']
			],
			//
			[
				['data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists?params=pE","data":{"bar":"foo"}}'],
				['data-rule-remote' => static::routeUrl() . '/active_url?params='],
				'',
				['data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists-active_url?params[]=pE&params[]=","data":{"bar":"foo"}}'],
				[]
			],
			[
				['data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists?params=pE","data":{"bar":"foo"}}'],
				['data-rule-remote' => '{"url":"' . static::routeUrl() . '/active_url?params=","data":{"foo":"X"}}'],
				'',
				['data-rule-remote' => '{"url":"' . static::routeUrl() . '/exists-active_url?params[]=pE&params[]=","data":{"bar":"foo","foo":"X"}}'],
				[]
			],
		);
	}

}
