<?php namespace Bllim\Laravalid;

use Illuminate\Html\HtmlBuilder;

class FormBuilderTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|Converter\Base\Converter
	 */
	protected $converter;

	/**
	 * @var FormBuilder
	 */
	protected $form;

	protected function setUp()
	{
		parent::setUp();

		$app = Converter\ConverterTest::initApplicationMock($this);
		$this->converter = ($this->getName(false) == 'testIntegration')
			? new Converter\JqueryValidation\Converter($app)
			: $this->getMock(__NAMESPACE__ . '\Converter\Base\Converter', array('set', 'reset', 'convert'), array(), '', false);

		$this->form = new FormBuilder(new HtmlBuilder($url = $app['url']), $url, '_csrf_token', $this->converter);

		$session = $this->getMock('Illuminate\Session\Store', array('get'), array(), '', false);
		$this->form->setSessionStore($session);
		$session->expects($this->any())->method('get')->willReturnArgument(1);
	}

	/**
	 * @param string $paramValue
	 * @param string $expectedValue
	 * @dataProvider dataForTestRawAttributeName
	 */
	public function testRawAttributeName($paramValue, $expectedValue)
	{
		$this->converter->expects($this->once())->method('convert')->willReturnArgument(0);
		$value = Converter\ConverterTest::invokeMethod($this->form, 'getValidationAttributes', $paramValue);

		$this->assertEquals($expectedValue, $value);
	}

	/**
	 * @return array
	 */
	public function dataForTestRawAttributeName()
	{
		return array(
			array('Bar', 'Bar'),
			array('bar_Foo[]', 'bar_Foo'),
			array('Foo[][1]', 'Foo'),
			array('foo[1][]', 'foo'),
			array('Foo[Bar][0]', 'Foo'),
		);
	}

	public function testSetValidation()
	{
		$this->converter->expects($this->once())->method('set')->with(null);
		$this->form->setValidation(null);

		$this->converter->expects($this->once())->method('reset');
		$this->form->resetValidation();
	}

	public function testOpen()
	{
		$this->converter->expects($this->once())->method('set')->with($rules = array('bar'));
		$html = $this->form->open(array('url' => '/', 'method' => 'get'), $rules);

		$this->assertEquals('<form method="GET" action="/" accept-charset="UTF-8">', $html);
	}

	public function testModel()
	{
		$this->converter->expects($this->exactly(2))->method('set')
			->withConsecutive(array($rules = array('foo' => true)), array(null));

		$html = $this->form->model(new \stdClass(), array('url' => '/', 'method' => 'get'), $rules);

		$this->assertEquals('<form method="GET" action="/" accept-charset="UTF-8">', $html);
	}

	public function testClose()
	{
		$this->converter->expects($this->once())->method('reset');
		$this->assertEquals('</form>', $this->form->close());
	}

	public function testInput()
	{
		$this->converter->expects($this->exactly(2))->method('convert')
			->withConsecutive(array('foo', 'date'), array('bar', 'url'))
			->willReturnOnConsecutiveCalls(array('foo' => 'date'), array());
		$html = $this->form->input('date', 'foo[]');

		$this->assertStringEndsWith('>', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' foo="date"', $html);

		$html = $this->form->url('bar[]');

		$this->assertStringEndsWith('>', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' type="url"', $html);
		$this->assertContains(' name="bar[]"', $html);
	}

	public function testTextArea()
	{
		$this->converter->expects($this->once())->method('convert')->with('bar', null)->willReturn(array());
		$html = $this->form->textarea('bar[]');

		$this->assertStringEndsWith('>', $html);
		$this->assertStringStartsWith('<textarea ', $html);
		$this->assertContains(' name="bar[]"', $html);
	}

	public function testSelect()
	{
		$this->converter->expects($this->once())->method('convert')->with('bar_foo', null)->willReturn(array());
		$html = $this->form->select('bar_foo');

		$this->assertStringEndsWith('>', $html);
		$this->assertStringStartsWith('<select ', $html);
		$this->assertContains(' name="bar_foo"', $html);
	}

	public function testCheckbox()
	{
		$this->converter->expects($this->exactly(2))->method('convert')
			->withConsecutive(array('foo', 'checkbox'), array('bar', 'radio'))->willReturn(array());
		$html = $this->form->checkbox('foo[]');

		$this->assertStringEndsWith('>', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' name="foo[]"', $html);
		$this->assertContains(' type="checkbox"', $html);

		$html = $this->form->radio('bar[]');

		$this->assertStringEndsWith('>', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' name="bar[]"', $html);
		$this->assertContains(' type="radio"', $html);
	}

	static $validationRules = array(
		'uid' => array('required', 'min:3', 'max:30', 'alpha_num', 'exists:users,uid'),
		'email' => 'required|max:255|email|unique:users,email',
		'url' => array('required', 'max:255', 'url', 'unique:users,url', 'active_url'),
		'name' => 'max:255|alpha',
		//
		'pwd' => array('min:6', 'max:15', 'regex:/^[0-9]+[xX][0-9]+$/'),
		'confirm_pwd' => 'min:6|max:15|same:pwd',
		//
		'first_name' => 'required_with:name|max:100|different:name',
		'last_name' => 'required_without:first_name|max:100',
		//
		'photo' => 'max:1000|url|active_url:anon',
		'gender' => 'boolean',
		'birthdate' => 'date|after:1900-01-01|before:2018-01-01',
		'phone' => 'between:20,30',
		'country' => 'in:US,VN',
		//
		'rating' => 'numeric|between:0,100',
		'duration' => 'integer|min:0|max:18000',
		//
		'description' => 'max:2000',
		'roles' => 'array|min:1|max:3',
		//
		'avatar' => 'image|min:30|max:300',
		'settings' => 'array|between:0,5',
		'client_ip' => 'ip',
		'upload' => 'mimes:csv,txt|between:100,500',
	);

	public function testIntegration()
	{
		$html = $this->form->model(new \stdClass(), array('url' => '/'), static::$validationRules);
		$this->assertEquals('<form method="POST" action="/" accept-charset="UTF-8"><input name="_token" type="hidden" value="_csrf_token">', $html);
		$this->assertEquals(static::$validationRules, $this->converter->getValidationRules());

		$html = $this->form->text('uid');
		$this->assertStringEndsWith(' name="uid" type="text">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' required="required" data-msg-required="The UID field is required."', $html);
		$this->assertContains(' minlength="3" data-msg-minlength="The UID must be at least {0} characters."', $html);
		$this->assertContains(' maxlength="30" data-msg-maxlength="The UID may not be greater than {0} characters."', $html);
		$this->assertContains(' pattern="^[A-Za-z0-9_.-]+$" data-msg-pattern="The UID may only contain letters and numbers."', $html);
		$this->assertContains(' data-rule-remote="/laravalid/exists?params=dXNlcnMsdWlk" data-msg-remote="The UID did not exist."', $html);

		$html = $this->form->email('email', null, array('placeholder' => 'Email'));
		$this->assertStringEndsWith(' name="email" type="email">', $html);
		$this->assertStringStartsWith('<input placeholder="Email"', $html);
		$this->assertContains(' required="required" data-msg-required="The email field is required."', $html);
		$this->assertNotContains(' data-rule-email=', $html);
		$this->assertContains(' data-msg-email="The email must be a valid email address."', $html);
		$this->assertContains(' maxlength="255" data-msg-maxlength="The email may not be greater than {0} characters."', $html);
		$this->assertContains(' data-rule-remote="/laravalid/unique?params=dXNlcnMsZW1haWw" data-msg-remote="The email has already been taken."', $html);

		$html = $this->form->url('url');
		$this->assertStringEndsWith(' name="url" type="url">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' required="required" data-msg-required="The URL field is required."', $html);
		$this->assertContains(' maxlength="255" data-msg-maxlength="The URL may not be greater than {0} characters."', $html);
		$this->assertNotContains(' data-rule-url=', $html);
		$this->assertContains(' data-msg-url="The URL format is invalid."', $html);
		$this->assertContains(' data-rule-remote="/laravalid/unique-active_url?params[]=dXNlcnMsdXJs&amp;params[]="', $html);
		$this->assertNotContains(' data-msg-remote=', $html);

		$html = $this->form->text('name');
		$this->assertStringEndsWith(' name="name" type="text">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' maxlength="255" data-msg-maxlength="The name may not be greater than {0} characters."', $html);
		$this->assertContains(' pattern="^[A-Za-z_.-]+$" data-msg-pattern="The name may only contain letters."', $html);

		$html = $this->form->password('pwd');
		$this->assertStringEndsWith(' name="pwd" type="password" value="">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' minlength="6" data-msg-minlength="The password must be at least {0} characters."', $html);
		$this->assertContains(' maxlength="15" data-msg-maxlength="The password may not be greater than {0} characters."', $html);
		$this->assertContains(' pattern="^[0-9]+[xX][0-9]+$" data-msg-pattern="The password format is invalid."', $html);

		$html = $this->form->password('confirm_pwd');
		$this->assertStringEndsWith(' name="confirm_pwd" type="password" value="">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' minlength="6" data-msg-minlength="The confirmation must be at least {0} characters."', $html);
		$this->assertContains(' maxlength="15" data-msg-maxlength="The confirmation may not be greater than {0} characters."', $html);
		$this->assertContains(' data-rule-equalto=":input[name=&#039;pwd&#039;]" data-msg-equalto="The confirmation and password must match."', $html);

		$html = $this->form->text('first_name');
		$this->assertStringEndsWith(' name="first_name" type="text">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertNotContains(' required=', $html);
		$this->assertContains(' data-rule-required=":input:enabled[name=&#039;name&#039;]:not(:checkbox):not(:radio):filled,input:enabled[name=&#039;name&#039;]:checked"', $html);
		$this->assertContains(' data-msg-required="The first name field is required when name is present."', $html);
		$this->assertContains(' maxlength="100" data-msg-maxlength="The first name may not be greater than {0} characters."', $html);
		$this->assertContains(' data-rule-notequalto=":input[name=&#039;name&#039;]" data-msg-notequalto="The first name and name must be different."', $html);

		$html = $this->form->text('last_name');
		$this->assertStringEndsWith(' name="last_name" type="text">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertNotContains(' required=', $html);
		$this->assertContains(' data-rule-required=":input:enabled[name=&#039;first_name&#039;]:not(:checkbox):not(:radio):blank,input:enabled[name=&#039;first_name&#039;]:unchecked"', $html);
		$this->assertContains(' data-msg-required="The last name field is required when first name is not present."', $html);
		$this->assertContains(' maxlength="100" data-msg-maxlength="The last name may not be greater than {0} characters."', $html);

		$html = $this->form->text('photo');
		$this->assertStringEndsWith(' name="photo" type="text">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' maxlength="1000" data-msg-maxlength="The photo may not be greater than {0} characters."', $html);
		$this->assertContains(' data-rule-url="true" data-msg-url="The photo format is invalid."', $html);
		$this->assertContains(' data-rule-remote="/laravalid/active_url?params=YW5vbg" data-msg-remote="The photo is not a valid URL."', $html);

		$html = $this->form->select('gender', array('Female', 'Male'));// unsupported: boolean
		$this->assertEquals('<select name="gender"><option value="0">Female</option><option value="1">Male</option></select>', $html);

		$html = $this->form->input('date', 'birthdate');
		$this->assertStringEndsWith(' name="birthdate" type="date">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertNotContains(' data-rule-date=', $html);
		$this->assertContains(' data-msg-date="The birthdate is not a valid date."', $html);
		$this->assertContains(' min="1900-01-01" data-msg-min="The birthdate must be a date after {0}."', $html);
		$this->assertContains(' max="2018-01-01" data-msg-max="The birthdate must be a date before {0}."', $html);

		$html = $this->form->input('tel', 'phone');
		$this->assertStringEndsWith(' name="phone" type="tel">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' data-rule-rangelength="20,30" maxlength="30" data-msg-rangelength="The phone must be between {0} and {1} characters."', $html);

		$html = $this->form->select('country', array('US' => 'US', 'VN' => 'VN'));// unsupported: in
		$this->assertEquals('<select name="country"><option value="US">US</option><option value="VN">VN</option></select>', $html);

		$html = method_exists($this->form, 'number') ? $this->form->number('rating') : $this->form->input('number', 'rating');
		$this->assertStringEndsWith(' name="rating" type="number">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertNotContains(' data-rule-numeric=', $html);
		$this->assertNotContains(' data-rule-number=', $html);
		$this->assertContains(' data-msg-number="The rating must be a number."', $html);
		$this->assertContains(' data-rule-range="0,100" data-msg-range="The rating must be between {0} and {1}."', $html);

		$html = method_exists($this->form, 'number') ? $this->form->number('duration') : $this->form->input('number', 'duration');
		$this->assertStringEndsWith(' name="duration" type="number">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' data-rule-integer="true" data-msg-integer="The length must be an integer."', $html);
		$this->assertContains(' min="0" data-msg-min="The length must be at least {0}."', $html);
		$this->assertContains(' max="18000" data-msg-max="The length may not be greater than {0}."', $html);

		$html = $this->form->textarea('description', null, array('rows' => 5));
		$this->assertStringEndsWith(' name="description" cols="50"></textarea>', $html);
		$this->assertStringStartsWith('<textarea rows="5"', $html);
		$this->assertContains(' maxlength="2000" data-msg-maxlength="The description may not be greater than {0} characters."', $html);

		$html = $this->form->radio('roles[]', 'Admin');
		$this->assertStringEndsWith(' name="roles[]" type="radio" value="Admin">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' minlength="1" data-msg-minlength="The roles must have at least {0} items."', $html);
		$this->assertContains(' maxlength="3" data-msg-maxlength="The roles may not have more than {0} items."', $html);

		// overwrite 'accept'
		$html = $this->form->file('avatar', array('accept' => '.csv,image/png'));
		$this->assertStringEndsWith(' name="avatar" type="file">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertNotContains(' accept="image/*"', $html);
		$this->assertContains(' accept=".csv,image/png" data-msg-accept="The avatar must be an image."', $html);
		$this->assertContains(' minlength="30" data-msg-minlength="The avatar must be at least {0} kilobytes."', $html);
		$this->assertContains(' maxlength="300" data-msg-maxlength="The avatar may not be greater than {0} kilobytes."', $html);

		$html = $this->form->checkbox('settings[1][]', 'allow', true);
		$this->assertStringEndsWith(' name="settings[1][]" type="checkbox" value="allow">', $html);
		$this->assertStringStartsWith('<input checked="checked"', $html);
		$this->assertContains(' data-rule-rangelength="0,5" maxlength="5" data-msg-rangelength="The settings must have between {0} and {1} items."', $html);

		$html = $this->form->text('client_ip');
		$this->assertStringEndsWith(' name="client_ip" type="text">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' data-rule-ipv4="true" data-msg-ipv4="The client ip must be a valid IP address."', $html);

		$html = $this->form->file('upload');
		$this->assertStringEndsWith(' name="upload" type="file">', $html);
		$this->assertStringStartsWith('<input ', $html);
		$this->assertContains(' accept=".csv,.txt" data-msg-accept="The upload must be a file of type: csv, txt."', $html);
		$this->assertContains(' data-rule-rangelength="100,500" maxlength="500" data-msg-rangelength="The upload must be between {0} and {1} kilobytes."', $html);

		$this->assertEquals('</form>', $this->form->close());
		$this->assertEquals(array(), $this->converter->getValidationRules());
	}
}
