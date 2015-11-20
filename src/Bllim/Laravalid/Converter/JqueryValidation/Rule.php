<?php namespace Bllim\Laravalid\Converter\JqueryValidation;

use Lang;
use Bllim\Laravalid\Helper;

class Rule extends \Bllim\Laravalid\Converter\Base\Rule {

	/**
	 * Rules convertions which return attributes as an array
	 *
	 * @param  array ['name' => '', 'parameters' => []]
	 * @param  array 
	 * @param  array type of input
	 * @return  array
	 */
	
	public function email($parsedRule, $attribute, $type) 
	{
		return ['data-rule-email' => 'true'];
	}

	public function required($parsedRule, $attribute, $type) 
	{
		return ['data-rule-required' => 'true'];
	}

	public function url($parsedRule, $attribute, $type) 
	{
		return ['data-rule-url' => 'true'];
	}

	public function integer($parsedRule, $attribute, $type) 
	{
		return ['data-rule-number' => 'true'];
	}

	public function numeric($parsedRule, $attribute, $type) 
	{
		return ['data-rule-number' => 'true'];
	}

	public function ip($parsedRule, $attribute, $type) 
	{
		return ['data-rule-ipv4' => 'true'];
	}

	public function same($parsedRule, $attribute, $type) 
	{
		$value = vsprintf("*[name='%1s']", $parsedRule['parameters']);
		return ['data-rule-equalto' => $value];
	}

	public function regex($parsedRule, $attribute, $type) 
	{
		$rule = $parsedRule['parameters'][0];

		if(substr($rule, 0, 1) == substr($rule, -1, 1))
		{
			$rule = substr($rule, 1, -1);
		}

		return ['data-rule-regex' => $rule];
	}

	public function alpha($parsedRule, $attribute, $type) 
	{
		return ['data-rule-regex' => "^[A-Za-z _.-]+$"];
	}

	public function alphanum($parsedRule, $attribute, $type) 
	{
		return ['data-rule-regex' => "^[A-Za-z0-9 _.-]+$"];
	}

	public function image($parsedRule, $attribute, $type) 
	{
		return ['accept' => "image/*"];
	}

	public function date($parsedRule, $attribute, $type) 
	{
		return ['data-rule-date' => "true"];
	}

	public function min($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return ['data-rule-min' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
			
			default:
				return ['data-rule-minlength' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
		}
	}

	public function max($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return ['data-rule-max' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
			
			default:
				return ['data-rule-maxlength' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
		}
	}

	public function between($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return ['data-rule-range' => vsprintf("%1s,%2s", $parsedRule['parameters'])];
				break;
			
			default:
				return ['data-rule-minlength' => $parsedRule['parameters'][0], 'data-rule-maxlength' =>  $parsedRule['parameters'][1]];
				break;
		}
	}

	public function unique($parsedRule, $attribute, $type) 
	{
		$param = implode(',', $parsedRule['parameters']);
		$encrpytedParam = Helper::encrypt($param);
		$route = \Config::get('laravalid.route', 'laravalid');
		return ['data-rule-remote' => url('/' . $route . '/unique').'?params=' . $encrpytedParam];
	}
	
	public function exists($parsedRule, $attribute, $type) 
	{
		$param = implode(',', $parsedRule['parameters']);
		$encrpytedParam = Helper::encrypt($param);
		$route = \Config::get('laravalid.route', 'laravalid');
		return ['data-rule-remote' => url('/' . $route . '/exists').'?params=' . $encrpytedParam];
	}


}