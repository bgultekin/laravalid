<?php namespace Bllim\Laravalid\Converter\Base;
/**
 * Some description...
 * 
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

use Bllim\Laravalid\Helper;

abstract class Route extends Container {

	public function convert($name, $parameters)
	{
		$methodName = strtolower($name);

		if(isset($this->customMethods[$methodName]))
		{
			return call_user_func_array($this->customMethods[$methodName], $parameters);
		}

		if(method_exists($this, $methodName))
		{
			return call_user_func_array([$this, $methodName], $parameters);
		}

		return $this->defaultRoute($name, $parameters);
	}

	public function defaultRoute($name, $parameters)
	{
		$validator = \Validator::make(
		    array('input' => $parameters['value']),
		    array('input' => $name.':'.Helper::decrypt($parameters['validationParameters']))
		);

		return ['valid' => !$validator->fails(), 'messages' => $validator->messages()];
	}
}