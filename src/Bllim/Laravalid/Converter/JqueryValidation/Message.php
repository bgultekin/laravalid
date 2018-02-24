<?php namespace Bllim\Laravalid\Converter\JqueryValidation;

class Message extends \Bllim\Laravalid\Converter\Base\Message {

	public function ip($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name']);
		return array('data-msg-ipv4' => $message);
	}
	
	public function same($parsedRule, $attribute)
	{
		$other = $this->getValidationAttribute(reset($parsedRule['parameters']));
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], compact('other'));
		return array('data-msg-equalto' => $message);
	}
	
	public function different($parsedRule, $attribute)
	{
		$other = $this->getValidationAttribute(reset($parsedRule['parameters']));
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], compact('other'));
		return array('data-msg-notequalto' => $message);
	}
	
	public function alpha($parsedRule, $attribute)
	{
		return $this->regex($parsedRule, $attribute);
	}

	public function alpha_num($parsedRule, $attribute)
	{
		return $this->regex($parsedRule, $attribute);
	}

	public function regex($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name']);
		return array('data-msg-pattern' => $message);
	}

	public function image($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name']);
		return array('data-msg-accept' => $message);
	}

	public function before($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], array('date' => '{0}'));
		return array('data-msg-max' => $message);
	}

	public function after($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], array('date' => '{0}'));
		return array('data-msg-min' => $message);
	}

	public function numeric($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name']);
		return array('data-msg-number' => $message);
	}

	public function max($parsedRule, $attribute, $type)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], array('max' => '{0}'), $type);
		switch ($type) {
			case 'numeric':
				return array('data-msg-max' => $message);
				break;
			
			default:
				return array('data-msg-maxlength' => $message);
				break;
		}
	}
	
	public function min($parsedRule, $attribute, $type)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], array('min' => '{0}'), $type);
		switch ($type) {
			case 'numeric':
				return array('data-msg-min' => $message);
				break;
			
			default:
				return array('data-msg-minlength' => $message);
				break;
		}
	}
	
	public function between($parsedRule, $attribute, $type)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], array('min' => '{0}', 'max' => '{1}'), $type);
		switch ($type) {
			case 'numeric':
				return array('data-msg-range' => $message);
				break;
			
			default:
				return array('data-msg-rangelength' => $message/*, 'data-msg-maxlength' => $message*/);
				break;
		}
	}

	public function unique($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name']);
		return array('data-msg-remote' => $message);
	}

	public function exists($parsedRule, $attribute)
	{
		return $this->unique($parsedRule, $attribute);
	}

	public function required_with($parsedRule, $attribute)
	{
		$values = implode(', ', array_map(array($this, 'getValidationAttribute'), $parsedRule['parameters']));
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], compact('values'));
		return array('data-msg-required' => $message);
	}

	public function required_without($parsedRule, $attribute)
	{
		return $this->required_with($parsedRule, $attribute);
	}

	public function active_url($parsedRule, $attribute)
	{
		return $this->unique($parsedRule, $attribute);
	}

	public function mimes($parsedRule, $attribute)
	{
		$message = $this->getValidationMessage($attribute, $parsedRule['name'], array('values' => implode(', ', $parsedRule['parameters'])));
		return array('data-msg-accept' => $message);
	}

}
