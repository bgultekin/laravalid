<?php namespace Bllim\Laravalid\Converter\JqueryValidation;

class Converter extends \Bllim\Laravalid\Converter\Base\Converter {

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