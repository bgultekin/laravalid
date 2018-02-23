<?php namespace Bllim\Laravalid\Converter;

class MessageTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Illuminate\Translation\Translator
	 */
	protected $translator;

	/**
	 * @var JqueryValidation\Message
	 */
	protected $message;

	protected function setUp()
	{
		parent::setUp();

		$this->translator = $this->getMock('Illuminate\Translation\Translator', array('has', 'get'), array(), '', false);
		$this->message = new JqueryValidation\Message($this->translator);
	}

	public function testExtend()
	{
		$this->assertNull($this->message->convert('foo'));

		$this->message->extend('foo', $func = function () {
			return func_get_args();
		});
		$this->assertEquals($params = array('Bar', array('zoo' => 'foo')), $this->message->convert('Foo', $params));

		$this->message->extend('ip', $func);
		$this->assertEquals($params = array(array('name' => 'Ip'), 'barFoo'), $this->message->convert('IP', $params));
	}

	public function testGetValidationMessage()
	{
		$this->translator->expects($this->exactly(3))->method('has')
			->withConsecutive(array($this->anything()), array('validation.custom.lastName.email'), array($this->anything()))
			->willReturnOnConsecutiveCalls(false, true, false);
		//
		$this->translator->expects($this->exactly(6))->method('get')
			->withConsecutive(
				array('validation.attributes.first_name'), array('validation.active_url', array('other' => 'old_name', 'attribute' => 'first name')),
				array('validation.attributes.lastName'), array('validation.custom.lastName.email', array('max' => '100', 'attribute' => 'Last name')),
				array('validation.attributes.foo'), array('validation.max.numeric', array('attribute' => 'Bar'))
			)
			->willReturnOnConsecutiveCalls(
				'validation.attributes.first_name', 'first name === old_name',
				'Last name', 'Last name <= 100',
				'Bar', 'validation.max.numeric'
			);

		$value = $this->message->getValidationMessage('first_name', 'activeUrl', array('other' => 'old_name'));
		$this->assertEquals('first name === old_name', $value);

		$value = $this->message->getValidationMessage('lastName', 'email', array('max' => '100'));
		$this->assertEquals('Last name <= 100', $value);

		$value = $this->message->getValidationMessage('foo', 'max', array(), 'numeric');
		$this->assertEquals('validation.max.numeric', $value);
	}

	/**
	 * @param string $name
	 * @param array $params
	 * @param array $expected
	 * @dataProvider dataForTestAllRules
	 */
	public function testAllRules($name = '', $params = array(), $expected = array())
	{
		$this->translator->expects($this->exactly(2))->method('has')->willReturn(preg_match('/^[A-Z]/', $name));
		$this->translator->expects($this->atLeast(4))->method('get')->willReturnCallback(function ($key, $data = null) {
			return $key . (empty($data) ? '' : json_encode($data));
		});

		$value = call_user_func_array(array($this->message, strtolower($name)), $params);
		$this->assertEquals($expected, $value);

		$this->assertEquals($expected, $this->message->convert($name, $params));
	}

	public function dataForTestAllRules()
	{
		return array(
			array('IP', array(array('name' => 'Ip'), 'barFoo', 'string'), array('data-msg-ipv4' => 'validation.custom.barFoo.ip{"attribute":"bar foo"}')),
			array('same', array(array('name' => 'Same', 'parameters' => array('X')), 'barFoo'), array('data-msg-equalto' => 'validation.same{"other":"x","attribute":"bar foo"}')),
			array('different', array(array('name' => 'different', 'parameters' => array('foo')), 'bar'), array('data-msg-notequalto' => 'validation.different{"other":"foo","attribute":"bar"}')),
			array('Alpha', array(array('name' => 'alpha'), 'barFoo'), array('data-msg-pattern' => 'validation.custom.barFoo.alpha{"attribute":"bar foo"}')),
			array('alpha_Num', array(array('name' => 'AlphaNum'), 'barFoo', 'numeric'), array('data-msg-pattern' => 'validation.alpha_num{"attribute":"bar foo"}')),
			array('Regex', array(array('name' => 'Regex'), 'barFoo'), array('data-msg-pattern' => 'validation.custom.barFoo.regex{"attribute":"bar foo"}')),
			//
			array('image', array(array('name' => 'Image'), 'barFoo', 'file'), array('data-msg-accept' => 'validation.image{"attribute":"bar foo"}')),
			array('before', array(array('name' => 'Before'), 'Bar', 'date'), array('data-msg-max' => 'validation.before{"date":"{0}","attribute":"bar"}')),
			array('after', array(array('name' => 'after', 'parameters' => array('1900-01-01')), 'bar', 'date'), array('data-msg-min' => 'validation.after{"date":"{0}","attribute":"bar"}')),
			array('Numeric', array(array('name' => 'Numeric'), 'barFoo', 'numeric'), array('data-msg-number' => 'validation.custom.barFoo.numeric{"attribute":"bar foo"}')),
			//
			array('Max', array(array('name' => 'Max'), 'Foo', 'numeric'), array('data-msg-max' => 'validation.custom.Foo.max.numeric{"max":"{0}","attribute":"foo"}')),
			array('max', array(array('name' => 'Max', 'parameters' => array('10')), 'foo', 'file'), array('data-msg-maxlength' => 'validation.max.file{"max":"{0}","attribute":"foo"}')),
			array('Min', array(array('name' => 'Min'), 'Bar', 'numeric'), array('data-msg-min' => 'validation.custom.Bar.min.numeric{"min":"{0}","attribute":"bar"}')),
			array('min', array(array('name' => 'Min', 'parameters' => array('10')), 'bar', 'string'), array('data-msg-minlength' => 'validation.min.string{"min":"{0}","attribute":"bar"}')),
			//
			array('between', array(array('name' => 'between'), 'barFoo', 'numeric'), array('data-msg-range' => 'validation.between.numeric{"min":"{0}","max":"{1}","attribute":"bar foo"}')),
			array(
				'Between',
				array(array('name' => 'Between', 'parameters' => array('1', '10')), 'barFoo', 'array'),
				array('data-msg-rangelength' => 'validation.custom.barFoo.between.array{"min":"{0}","max":"{1}","attribute":"bar foo"}')
			),
			//
			array(
				'required_With',
				array(array('name' => 'requiredWith', 'parameters' => array('bar', 'foo2')), 'Foo', 'string'),
				array('data-msg-required' => 'validation.required_with{"values":"bar, foo2","attribute":"foo"}')
			),
			array(
				'Required_withOut',
				array(array('name' => 'RequiredWithout', 'parameters' => array('foo2')), 'foo', 'array'),
				array('data-msg-required' => 'validation.custom.foo.required_without{"values":"foo2","attribute":"foo"}')
			),
			array(
				'Mimes',
				array(array('name' => 'Mimes', 'parameters' => array('csv', 'pdf')), 'barFoo', 'file'),
				array('data-msg-accept' => 'validation.custom.barFoo.mimes{"values":"csv, pdf","attribute":"bar foo"}')
			),
			//
			array('Unique', array(array('name' => 'unique'), 'Bar', 'string'), array('data-msg-remote' => 'validation.custom.Bar.unique{"attribute":"bar"}')),
			array('exists', array(array('name' => 'exists', 'parameters' => array('Tbl,f')), 'BarFoo'), array('data-msg-remote' => 'validation.exists{"attribute":"bar foo"}')),
			array('active_Url', array(array('name' => 'ActiveUrl', 'parameters' => array('anon')), 'barFoo'), array('data-msg-remote' => 'validation.active_url{"attribute":"bar foo"}')),
		);
	}
}
