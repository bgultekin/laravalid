<?php namespace Bllim\Laravalid\Converter\Base;
/**
 * This container class brings to extended class extendibility and also base convert function
 * 
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

abstract class Container {

	protected $customMethods = array();

	public function convert($name, $parameters = array())
	{
		$methodName = strtolower($name);

		if (isset($this->customMethods[$methodName]))
		{
			return call_user_func_array($this->customMethods[$methodName], $parameters);
		}

		if (method_exists($this, $methodName))
		{
			return call_user_func_array(array($this, $methodName), $parameters);
		}

		return null;
	}

	public function extend($name, $function)
	{
		$this->customMethods[$name] = $function;
	}

}