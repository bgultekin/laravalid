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

        $this->translator = $this->getMock('Illuminate\Translation\Translator', ['has', 'get'], [], '', false);
        $this->message = new JqueryValidation\Message($this->translator);
    }

    public function testExtend()
    {
        $this->assertEmpty($this->message->convert('foo'));

        $this->message->extend('foo', $func = function () {
            return func_get_args();
        });
        $this->assertEquals($params = ['Bar', ['zoo' => 'foo']], $this->message->convert('Foo', $params));

        $this->message->extend('ip', $func);
        $this->assertEquals($params = [['name' => 'Ip'], 'barFoo'], $this->message->convert('IP', $params));
    }

    public function testGetValidationMessage()
    {
        $this->translator->expects($this->exactly(3))->method('has')
            ->withConsecutive([$this->anything()], ['validation.custom.lastName.email'], [$this->anything()])
            ->willReturnOnConsecutiveCalls(false, true, false);
        //
        $this->translator->expects($this->exactly(6))->method('get')
            ->withConsecutive(
                ['validation.attributes.first_name'], ['validation.active_url', ['other' => 'old_name', 'attribute' => 'first name']],
                ['validation.attributes.lastName'], ['validation.custom.lastName.email', ['max' => '100', 'attribute' => 'Last name']],
                ['validation.attributes.foo'], ['validation.max.numeric', ['attribute' => 'Bar']]
            )
            ->willReturnOnConsecutiveCalls(
                'validation.attributes.first_name', 'first name === old_name',
                'Last name', 'Last name <= 100',
                'Bar', 'validation.max.numeric'
            );

        $value = $this->message->getValidationMessage('first_name', 'activeUrl', ['other' => 'old_name']);
        $this->assertEquals('first name === old_name', $value);

        $value = $this->message->getValidationMessage('lastName', 'email', ['max' => '100']);
        $this->assertEquals('Last name <= 100', $value);

        $value = $this->message->getValidationMessage('foo', 'max', [], 'numeric');
        $this->assertEquals('validation.max.numeric', $value);
    }

    /**
     * @param string $name
     * @param array $params
     * @param array $expected
     * @dataProvider dataForTestAllRules
     */
    public function testAllRules($name = '', $params = [], $expected = [])
    {
        $this->translator->expects($this->exactly(2))->method('has')->willReturn(preg_match('/^[A-Z]/', $name));
        $this->translator->expects($this->atLeast(4))->method('get')->willReturnCallback(function ($key, $data = null) {
            return $key . (empty($data) ? '' : json_encode($data));
        });

        $value = call_user_func_array([$this->message, strtolower($name)], $params);
        $this->assertEquals($expected, $value);

        $this->assertEquals($expected, $this->message->convert($name, $params));
    }

    public function dataForTestAllRules()
    {
        return array(
            ['IP', [['name' => 'Ip'], 'barFoo', 'string'], ['data-msg-ipv4' => 'validation.custom.barFoo.ip{"attribute":"bar foo"}']],
            ['same', [['name' => 'Same', 'parameters' => ['X']], 'barFoo'], ['data-msg-equalto' => 'validation.same{"other":"x","attribute":"bar foo"}']],
            ['different', [['name' => 'different', 'parameters' => ['foo']], 'bar'], ['data-msg-notequalto' => 'validation.different{"other":"foo","attribute":"bar"}']],
            ['Alpha', [['name' => 'alpha'], 'barFoo'], ['data-msg-pattern' => 'validation.custom.barFoo.alpha{"attribute":"bar foo"}']],
            ['alpha_Num', [['name' => 'AlphaNum'], 'barFoo', 'numeric'], ['data-msg-pattern' => 'validation.alpha_num{"attribute":"bar foo"}']],
            ['Regex', [['name' => 'Regex'], 'barFoo'], ['data-msg-pattern' => 'validation.custom.barFoo.regex{"attribute":"bar foo"}']],
            //
            ['image', [['name' => 'Image'], 'barFoo', 'file'], ['data-msg-accept' => 'validation.image{"attribute":"bar foo"}']],
            ['before', [['name' => 'Before'], 'Bar', 'date'], ['data-msg-max' => 'validation.before{"date":"{0}","attribute":"bar"}']],
            ['after', [['name' => 'after', 'parameters' => ['1900-01-01']], 'bar', 'date'], ['data-msg-min' => 'validation.after{"date":"{0}","attribute":"bar"}']],
            ['Numeric', [['name' => 'Numeric'], 'barFoo', 'numeric'], ['data-msg-number' => 'validation.custom.barFoo.numeric{"attribute":"bar foo"}']],
            //
            ['Max', [['name' => 'Max'], 'Foo', 'numeric'], ['data-msg-max' => 'validation.custom.Foo.max.numeric{"max":"{0}","attribute":"foo"}']],
            ['max', [['name' => 'Max', 'parameters' => ['10']], 'foo', 'file'], ['data-msg-maxlength' => 'validation.max.file{"max":"{0}","attribute":"foo"}']],
            ['Min', [['name' => 'Min'], 'Bar', 'numeric'], ['data-msg-min' => 'validation.custom.Bar.min.numeric{"min":"{0}","attribute":"bar"}']],
            ['min', [['name' => 'Min', 'parameters' => ['10']], 'bar', 'string'], ['data-msg-minlength' => 'validation.min.string{"min":"{0}","attribute":"bar"}']],
            //
            ['between', [['name' => 'between'], 'barFoo', 'numeric'], ['data-msg-range' => 'validation.between.numeric{"min":"{0}","max":"{1}","attribute":"bar foo"}']],
            [
                'Between',
                [['name' => 'Between', 'parameters' => ['1', '10']], 'barFoo', 'array'],
                ['data-msg-rangelength' => 'validation.custom.barFoo.between.array{"min":"{0}","max":"{1}","attribute":"bar foo"}']
            ],
            //
            [
                'required_With',
                [['name' => 'requiredWith', 'parameters' => ['bar', 'foo2']], 'Foo', 'string'],
                ['data-msg-required' => 'validation.required_with{"values":"bar, foo2","attribute":"foo"}']
            ],
            [
                'Required_withOut',
                [['name' => 'RequiredWithout', 'parameters' => ['foo2']], 'foo', 'array'],
                ['data-msg-required' => 'validation.custom.foo.required_without{"values":"foo2","attribute":"foo"}']
            ],
            [
                'Mimes',
                [['name' => 'Mimes', 'parameters' => ['csv', 'pdf']], 'barFoo', 'file'],
                ['data-msg-accept' => 'validation.custom.barFoo.mimes{"values":"csv, pdf","attribute":"bar foo"}']
            ],
            //
            ['Unique', [['name' => 'unique'], 'Bar', 'string'], ['data-msg-remote' => 'validation.custom.Bar.unique{"attribute":"bar"}']],
            ['exists', [['name' => 'exists', 'parameters' => ['Tbl,f']], 'BarFoo'], ['data-msg-remote' => 'validation.exists{"attribute":"bar foo"}']],
            ['active_Url', [['name' => 'ActiveUrl', 'parameters' => ['anon']], 'barFoo'], ['data-msg-remote' => 'validation.active_url{"attribute":"bar foo"}']],
        );
    }
}
