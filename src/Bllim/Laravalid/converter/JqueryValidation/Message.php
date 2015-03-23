<?php namespace Bllim\Laravalid\Converter\JqueryValidation;

use Lang;

class Message extends \Bllim\Laravalid\Converter\Base\Message {

	public function ip($parsedRule, $attribute, $type) 
	{
		$message = Lang::get('validation.'.$parsedRule['name'], ['attribute' => $attribute]);
		return ['data-msg-ipv4' => $message];
	}
	
	public function alpha($parsedRule, $attribute, $type) 
	{
		$message = Lang::get('validation.'.$parsedRule['name'], ['attribute' => $attribute]);
		return ['data-msg-regex' => $message];
	}
	
	public function alphanum($parsedRule, $attribute, $type) 
	{
		$message = Lang::get('validation.'.$parsedRule['name'], ['attribute' => $attribute]);
		return ['data-msg-regex' => $message];
	}
	
	public function max($parsedRule, $attribute, $type)
	{
		$message = Lang::get('validation.'.$parsedRule['name'].'.'.$type, ['attribute' => $attribute, 'max' => $parsedRule['parameters'][0]]);
		switch ($type) {
			case 'numeric':
				return ['data-msg-max' => $message];
				break;
			
			default:
				return ['data-msg-maxlength' => $message];
				break;
		}
	}
	
	public function min($parsedRule, $attribute, $type)
	{
		$message = Lang::get('validation.'.$parsedRule['name'].'.'.$type, ['attribute' => $attribute, 'min' => $parsedRule['parameters'][0]]);
		switch ($type) {
			case 'numeric':
				return ['data-msg-min' => $message];
				break;
			
			default:
				return ['data-msg-minlength' => $message];
				break;
		}
	}
	
	public function between($parsedRule, $attribute, $type)
	{
		$message = Lang::get('validation.'.$parsedRule['name'].'.'.$type, ['attribute' => $attribute, 'min' => $parsedRule['parameters'][0], 'max' => $parsedRule['parameters'][1]]);
		switch ($type) {
			case 'numeric':
				return ['data-msg-range' => $message];
				break;
			
			default:
				return ['data-msg-minlength' => $message, 'data-msg-maxlength' => $message];
				break;
		}
	}

}