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
	 * Current form name
	 *
	 * @var string
	 */
	protected $currentFormName = null;


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
	public function set($rules, $formName = null)
	{
		if($rules === null) return;

		$this->validationRules[$formName] = $rules;
	}

	/**
	 * Reset validation rules
	 *
	 */
	public function reset()
	{
		if(isset($this->validationRules[$this->currentFormName]))
		{
			unset($this->validationRules[$this->currentFormName]);
		}
		else if(isset($this->validationRules[null]))
		{
			unset($this->validationRules[null]);
		}
	}

	/**
	 * Set form name in order to get related validation rules
	 *
	 * @param array $formName 		Form name
	 *
	 */
	public function setFormName($formName)
	{
		$this->currentFormName = $formName;
	}


	/**
	 * Get all given validation rules
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function getValidationRules()
	{
		if(isset($this->validationRules[$this->currentFormName]))
		{
			return $this->validationRules[$this->currentFormName];
		}
		else if(isset($this->validationRules[null]))
		{
			return $this->validationRules[null];			
		}

		return null;
	}

	/**
	 * Returns validation rules for given input name
	 *
	 * @return string
	 */
	protected function getValidationRule($inputName)
	{
		return is_array($this->getValidationRules()[$inputName])
		 ? $this->getValidationRules()[$inputName]
		 : explode('|', $this->getValidationRules()[$inputName]);
	}

	/**
	 * Checks if there is a rules for given input name
	 *
	 * @return string
	 */
	protected function checkValidationRule($inputName)
	{
		return isset($this->getValidationRules()[$inputName]);
	}


	public function convert($inputName)
	{		

		$inputName = $this->formatInputName($inputName);
		
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

		$explodedRule = explode(':', $rule, 2);
		$ruleArray['name'] = array_shift($explodedRule);
		$ruleArray['parameters'] = $this->parseParameters($ruleArray['name'], array_shift($explodedRule));

		return $ruleArray;
	}

	/**
	 * Parse a parameter list.
	 *
	 * @param  string  $rule
	 * @param  string  $parameter
	 * @return array
	 */
	protected function parseParameters($rule, $parameter)
	{
		if (strtolower($rule) == 'regex') return array($parameter);

		return str_getcsv($parameter);
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

	/** 
	 * Format recursive array like input names to laravel validation format
	 * Example name[en] will transform to name.en
	 * @param  string $inputName 
	 * @return string
	 */
	protected function formatInputName($inputName)
	{
		preg_match_all("/\[(\s*[\w]*\s*)\]/", $inputName, $output, PREG_PATTERN_ORDER);
		
		if(!isset($output[1])) return $inputName;

		$replaceWith = $output[1];
		$replace     = $output[0];

		foreach($replaceWith as $key => $r) 
			$replaceWith[$key] = '.' . $r;
		
		return str_replace($replace, $replaceWith, $inputName);
	}

}