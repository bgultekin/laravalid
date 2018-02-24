<?php namespace Bllim\Laravalid\Converter\Base;
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
	 * @var Rule
	 */
	protected static $rule;

	/**
	 * Message converter class instance
	 *
	 * @var Message
	 */
	protected static $message;

	/**
	 * Route redirector class instance
	 *
	 * @var Route
	 */	
	protected static $route;

	/**
	 * Laravel validation rules.
	 *
	 * @var array
	 */
	protected $validationRules = array();

	protected static $multiParamRules = array(
		'between', 'digits_between',
		'in', 'not_in',
		'mimes',
		'required_if', 'required_with', 'required_with_all', 'required_without', 'required_without_all',
		'exists', 'unique',
	);

	/**
	 * Rules which specify input type is file
	 *
	 * @var array
	 */
	protected static $fileRules = array('image', 'mimes');

	/**
	 * Rules which specify input type is numeric
	 *
	 * @var array
	 */
	protected static $numericRules = array('integer', 'numeric', 'digits', 'digits_between');

	/**
	 * @var bool
	 */
	protected $useLaravelMessages;

	/**
	 * @param \Illuminate\Container\Container $app
	 */
	public function __construct($app)
	{
		/* @var $config \Illuminate\Config\Repository */
		$config = $app['config'];
		$routeUrl = $app['url']->to($config->get('laravalid::route', 'laravalid'));

		$ns = substr($class = get_class($this), 0, strrpos($class, '\\')) . '\\';
		($class = $ns . 'Rule') and static::$rule = new $class($routeUrl, $app['encrypter']);
		($class = $ns . 'Message') and static::$message = new $class($app['translator']);
		($class = $ns . 'Route') and static::$route = new $class($app['validator'], $app['encrypter']);

		$this->useLaravelMessages = $config->get('laravalid::useLaravelMessages', true);
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
	 */
	public function set($rules)
	{
		if (isset($rules))
			$this->validationRules = (array)$rules;
	}

	/**
	 * Reset validation rules
	 */
	public function reset()
	{
		$this->validationRules = array();
	}

	/**
	 * Get all given validation rules
	 *
	 * @return array 		Laravel validation rules
	 */
	public function getValidationRules()
	{
		return $this->validationRules;
	}

	/**
	 * Returns validation rules for given input name
	 *
	 * @param string
	 * @return array
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
	 * @param string
	 * @return bool
	 */
	protected function checkValidationRule($inputName)
	{
		return isset($this->validationRules[$inputName]);
	}

	public function convert($inputName, $inputType = null)
	{
		if (!$this->checkValidationRule($inputName)) {
			return array();
		}

		$rules = $this->getValidationRule($inputName);
		$type = $this->getTypeOfInput($rules);

		$outputAttributes = array();
		foreach ($rules as $rule)
		{
			$parsedRule = $this->parseValidationRule($rule);

			$ruleAttributes = $this->rule()->convert($parsedRule['name'], array($parsedRule, $inputName, $type));
			if (!empty($ruleAttributes)) {
				$outputAttributes = $this->rule()->mergeOutputAttributes($outputAttributes, $ruleAttributes, $inputType);

				if (empty($ruleAttributes)) continue;
			}

			if ($this->useLaravelMessages)
			{
				$messageAttributes = $this->message()->convert($parsedRule['name'], array($parsedRule, $inputName, $type));

				// if empty message attributes
				if (empty($messageAttributes) && !empty($ruleAttributes))
				{
					$messageAttributes = $this->getDefaultErrorMessage($parsedRule['name'], $inputName);
				}

				if (!empty($messageAttributes))
					$outputAttributes += $messageAttributes;
			}
		}

		return $outputAttributes;
	}

	/**
	 * Get all rules and return type of input if rule specifies type
	 *
	 * @param array
	 * @return string
	 */
	protected function getTypeOfInput($rulesOfInput)
	{
		foreach ($rulesOfInput as $key => $rule) {
			$parsedRule = $this->parseValidationRule($rule);

			if (in_array($parsedRule['name'], static::$numericRules))
			{
				return 'numeric';
			}
			elseif ($parsedRule['name'] === 'array')
			{
				return 'array';
			}
			elseif (in_array($parsedRule['name'], static::$fileRules))
			{
				return 'file';
			}
		}

		return 'string';
	}

	/**
	 * Parses validation rule of laravel
	 *
	 * @param string
	 * @return array
	 */
	protected function parseValidationRule($rule)
	{
		$ruleArray = array();

		$parameters = explode(':', $rule, 2);
		$ruleArray['name'] = array_shift($parameters);

		if (empty($parameters) || !in_array(strtolower($ruleArray['name']), static::$multiParamRules)) {
			$ruleArray['parameters'] = $parameters;
		} else {
			$ruleArray['parameters'] = str_getcsv($parameters[0]);
		}

		return $ruleArray;
	}

	/**
	 * Gets default error message
	 *
	 * @param string $laravelRule
	 * @param string $attribute
	 * @return string
	 */
	protected function getDefaultErrorMessage($laravelRule, $attribute)
	{
		// getting user friendly validation message
		$message = $this->message()->getValidationMessage($attribute, $laravelRule);
		return array('data-msg-' . $laravelRule => $message);
	}

}