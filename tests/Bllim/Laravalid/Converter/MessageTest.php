<?php namespace Bllim\Laravalid\Converter;

use Illuminate\Translation\Translator;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Mockery\MockInterface|Translator
     */
    protected $translator;

    /**
     * @var JqueryValidation\Message
     */
    protected $message;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = \Mockery::mock(Translator::class);
        $this->message = new JqueryValidation\Message($this->translator);
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
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
        $this->translator->shouldReceive('has')->once()->andReturn(false);
        $this->translator->shouldReceive('get')->with('validation.attributes.first_name')->once()->andReturnUsing(function ($key) {
            return $key;
        });
        $this->translator->shouldReceive('get')
            ->with('validation.active_url', ['other' => 'old_name', 'attribute' => 'first name'])->once()
            ->andReturnUsing(function ($key, $data) {
                return ($key == 'validation.active_url') ? $data['attribute'] . ' === ' . $data['other'] : $key;
            });

        $value = $this->message->getValidationMessage('first_name', 'activeUrl', ['other' => 'old_name']);
        $this->assertEquals('first name === old_name', $value);

        //
        $this->translator->shouldReceive('has')->with('validation.custom.lastName.email')->once()->andReturn(true);
        $this->translator->shouldReceive('get')->with('validation.attributes.lastName')->once()->andReturn('Last name');
        $this->translator->shouldReceive('get')
            ->with('validation.custom.lastName.email', ['max' => '100', 'attribute' => 'Last name'])->once()
            ->andReturnUsing(function ($key, $data) {
                return ($key == 'validation.custom.lastName.email') ? $data['attribute'] . ' <= ' . $data['max'] : $key;
            });

        $value = $this->message->getValidationMessage('lastName', 'email', ['max' => '100']);
        $this->assertEquals('Last name <= 100', $value);

        //
        $this->translator->shouldReceive('has')->once()->andReturn(false);
        $this->translator->shouldReceive('get')->with('validation.attributes.foo')->once()->andReturn('Bar');
        $this->translator->shouldReceive('get')->with('validation.max.numeric', ['attribute' => 'Bar'])->once()->andReturnUsing(function ($key) {
            return $key;
        });

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
        $this->translator->shouldReceive('has')->times(2)->andReturn(preg_match('/^[A-Z]/', $name));
        $this->translator->shouldReceive('get')->atLeast()->times(4)->andReturnUsing(function ($key, $data = null) {
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
