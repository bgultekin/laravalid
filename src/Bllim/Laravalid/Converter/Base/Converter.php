<?php

namespace Bllim\Laravalid\Converter\Base;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Base converter class for converter plugins.
 * 
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 *
 * @see        Illuminate\Html\FormBuilder
 *
 * @version    0.9
 */
abstract class Converter
{
    /**
     * Rule converter class instance.
     *
     * @var Rule
     */
    protected static $rule;

    /**
     * Message converter class instance.
     *
     * @var Message
     */
    protected static $message;

    /**
     * Route redirector class instance.
     *
     * @var Route
     */
    protected static $route;

    /**
     * Laravel validation rules.
     *
     * @var array
     */
    protected $validationRules = [];

    /**
     * Current form name.
     *
     * @var string
     */
    protected $currentFormName = null;

    protected static $multiParamRules = [
        'between', 'digits_between',
        'in', 'not_in',
        'mimes',
        'required_if', 'required_with', 'required_with_all', 'required_without', 'required_without_all',
        'exists', 'unique',
    ];

    /**
     * Rules which specify input type is file.
     *
     * @var array
     */
    protected static $fileRules = ['image', 'mimes'];

    /**
     * Rules which specify input type is numeric.
     *
     * @var array
     */
    protected static $numericRules = ['integer', 'numeric', 'digits', 'digits_between'];

    /**
     * @var bool
     */
    protected $useLaravelMessages;

    public function __construct(Application $app)
    {
        /* @var $app Application|\ArrayAccess */
        $config = $app['config'];
        /* @var $config \Illuminate\Contracts\Config\Repository */
        $routeUrl = $app['url']->to($config->get('laravalid.route', 'laravalid'));

        $ns = substr(static::class, 0, -9) ?: '\\';
        ($class = $ns . 'Rule') and static::$rule = new $class($routeUrl, $app['encrypter']);
        ($class = $ns . 'Message') and static::$message = new $class($app['translator']);
        ($class = $ns . 'Route') and static::$route = new $class($app['validator'], $app[ResponseFactory::class], $app['encrypter']);

        $this->useLaravelMessages = $config->get('laravalid.useLaravelMessages', true);
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
     * Set rules for validation.
     *
     * @param array $rules Laravel validation rules
     * @param string $formName
     */
    public function set($rules, $formName = null)
    {
        if (isset($rules)) {
            // set form name in order to get related validation rules
            $this->currentFormName = $formName;

            $this->validationRules[$formName] = (array)$rules;
        }
    }

    /**
     * Reset validation rules.
     *
     * @param string|bool $formName Form name
     */
    public function reset($formName = false)
    {
        if (is_bool($formName))
            $formName = $this->currentFormName;

        if (array_key_exists($formName, $this->validationRules)) {
            $this->validationRules[$formName] = [];
        }
    }

    /**
     * Get all given validation rules.
     *
     * @param string|bool $formName
     * @return array Laravel validation rules
     */
    public function getValidationRules($formName = false)
    {
        if (is_bool($formName))
            $formName = $this->currentFormName;

        if (array_key_exists($formName, $this->validationRules)) {
            return $this->validationRules[$formName];
        }

        return [];
    }

    /**
     * Returns validation rules for given input name.
     *
     * @param string $inputName
     * @return array
     */
    protected function getValidationRule($inputName)
    {
        $rules = $this->getValidationRules();

        return is_array($rules[$inputName]) ? $rules[$inputName] : explode('|', $rules[$inputName]);
    }

    /**
     * Checks if there is a rules for given input name.
     *
     * @param string $inputName
     * @return bool
     */
    protected function checkValidationRule($inputName)
    {
        $rules = $this->getValidationRules();

        return isset($rules[$inputName]);
    }

    public function convert($inputName, $inputType = null)
    {
        if (!$this->checkValidationRule($inputName)) {
            return [];
        }

        $rules = $this->getValidationRule($inputName);
        $type = $this->getTypeOfInput($rules);

        $outputAttributes = [];
        foreach ($rules as $rule) {
            $parsedRule = $this->parseValidationRule($rule);

            $ruleAttributes = $this->rule()->convert($parsedRule['name'], [$parsedRule, $inputName, $type]);
            if (!empty($ruleAttributes)) {
                $outputAttributes = $this->rule()->mergeOutputAttributes($outputAttributes, $ruleAttributes, $inputType);

                if (empty($ruleAttributes)) continue;
            }

            if ($this->useLaravelMessages) {
                $messageAttributes = $this->message()->convert($parsedRule['name'], [$parsedRule, $inputName, $type]);

                // if empty message attributes
                if (empty($messageAttributes) && !empty($ruleAttributes)) {
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

            if (in_array($parsedRule['name'], static::$numericRules)) {
                return 'numeric';
            } elseif ($parsedRule['name'] === 'array') {
                return 'array';
            } elseif (in_array($parsedRule['name'], static::$fileRules)) {
                return 'file';
            }
        }

        return 'string';
    }

    /**
     * Parses validation rule of laravel.
     *
     * @param string
     * @return array
     */
    protected function parseValidationRule($rule)
    {
        $ruleArray = array();

        $explodedRule = explode(':', $rule, 2);
        $ruleArray['name'] = array_shift($explodedRule);

        if (empty($explodedRule) || !in_array(strtolower($ruleArray['name']), static::$multiParamRules)) {
            $ruleArray['parameters'] = $explodedRule;
        } else {
            $ruleArray['parameters'] = str_getcsv($explodedRule[0]);
        }

        return $ruleArray;
    }

    /**
     * Gets default error message.
     *
     * @param string $laravelRule
     * @param string $attribute
     * @return string
     */
    protected function getDefaultErrorMessage($laravelRule, $attribute)
    {
        // getting user friendly validation message
        $message = $this->message()->getValidationMessage($attribute, $laravelRule);

        return ['data-msg-'.$laravelRule => $message];
    }
}
