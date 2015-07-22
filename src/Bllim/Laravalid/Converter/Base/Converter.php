<?php namespace Bllim\Laravalid\Converter\Base;

use Bllim\Laravalid\Helper;

/**
 * Base converter class for converter plugins
 * 
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

abstract class Converter {

	/**
	 * Rule converter class instance
	 *
	 * @var array
	 */
	protected static $rule;

	/**
	 * Message converter class instance
	 *
	 * @var array
	 */
	protected static $message;

	/**
	 * Route redirecter class instance
	 *
	 * @var array
	 */	
	protected static $route;

	/**
	 * Rules which specify input type is numeric
	 *
	 * @var array
	 */
	protected $validationRules = [];


	/**
	 * Rules which specify input type is numeric
	 *
	 * @var array
	 */
	protected $numericRules = ['integer', 'numeric'];

	public function __construct()
	{
		self::$rule = new Rule();
		self::$message = new Message();
		self::$route = new Route();
	}

	public function rule()
	{
		return static::$rule;
	}

	public function message()
	{
		return static::$message;
	}

	public function route()
	{
		return static::$route;
	}

	/**
	 * Set rules for validation
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function set($rules)
	{
		if($rules === null) return;
		$this->validationRules = $rules;
	}

	/**
	 * Reset validation rules
	 *
	 */
	public function reset()
	{
		$this->validationRules = [];
	}


	/**
	 * Get all given validation rules
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function getValidationRules()
	{
		return $this->validationRules;
	}

	/**
	 * Returns validation rules for given input name
	 *
	 * @return string
	 */
	protected function getValidationRule($inputName)
	{
		return is_array($this->validationRules[$inputName])
		 ? $this->validationRules[$inputName]
		 : explode('|', $this->validationRules[$inputName]);
	}

	/**
	 * Checks if there is a rules for given input name
	 *
	 * @return string
	 */
	protected function checkValidationRule($inputName)
	{
		return isset($this->validationRules[$inputName]);
	}


	public function convert($inputName)
	{		
		$outputAttributes = [];

		if($this->checkValidationRule($inputName) === false)
		{
			return [];
		}

		$rules = $this->getValidationRule($inputName);
		$type = $this->getTypeOfInput($rules);

		foreach ($rules as $rule) 
		{
			$parsedRule = $this->parseValidationRule($rule);
			$outputAttributes = $outputAttributes + $this->rule()->convert($parsedRule['name'], [$parsedRule, $inputName, $type]);

			if(\Config::get('laravalid.useLaravelMessages', true))
			{
				$messageAttributes = $this->message()->convert($parsedRule['name'], [$parsedRule, $inputName, $type]);
				
				// if empty message attributes
				if(empty($messageAttributes))
				{
					$messageAttributes = $this->getDefaultErrorMessage($parsedRule['name'], $inputName);
				}
			}

			$outputAttributes = $outputAttributes + $messageAttributes;
		}
		
		return $outputAttributes;
	}

	/**
	 * Get all rules and return type of input if rule specifies type
	 * Now, just for numeric
	 *
	 * @return string
	 */
	protected function getTypeOfInput($rulesOfInput)
	{
		foreach ($rulesOfInput as $key => $rule) {
			$parsedRule = $this->parseValidationRule($rule);
			if(in_array($parsedRule['name'], $this->numericRules))
			{
				return 'numeric';
			}
			elseif ($parsedRule['name'] === 'array')
			{
				return 'array';
			}
		}

		return 'string';
	}

	/**
	 * Parses validition rule of laravel
	 *
	 * @return array
	 */
	protected function parseValidationRule($rule)
	{
		$ruleArray = ['name' => '', 'parameters' => []];

		$explodedRule = explode(':', $rule);
		$ruleArray['name'] = array_shift($explodedRule);
		$ruleArray['parameters'] = explode(',', array_shift($explodedRule));

		return $ruleArray;
	}

	/**
	 * Gets default error message
	 *
	 * @return string
	 */
	protected function getDefaultErrorMessage($laravelRule, $attribute)
	{
		// getting user friendly validation message
		$message = Helper::getValidationMessage($attribute, $laravelRule);

		return ['data-msg-'.$laravelRule => $message];
	}

}