<?php namespace Bllim\Laravalid\Plugin\JqueryValidation;

class Converter extends \Bllim\Laravalid\BaseConverter\Converter {

	public static $rule;
	public static $message;
	public static $route;

	public function __construct()
	{
		self::$rule = new Rule();
		self::$message = new Message();
		self::$route = new Route();
	}

}