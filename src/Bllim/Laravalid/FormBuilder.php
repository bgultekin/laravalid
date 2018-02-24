<?php namespace Bllim\Laravalid;
/**
 * This class is extending \Illuminate\Html\FormBuilder to make 
 * validation easy for both client and server side. Package convert 
 * laravel validation rules to javascript validation plugins while 
 * using laravel FormBuilder.
 *
 * USAGE: Just pass $rules to Form::open($options, $rules) and use.
 * You can also pass by using Form::setValidation from controller or router
 * for coming first Form::open.
 * When Form::close() is used, $rules are reset.
 *
 * NOTE: If you use min, max, size, between and type of input is different from string
 * don't forget to specify the type (by using numeric, integer).
 *
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

class FormBuilder extends \Illuminate\Html\FormBuilder {

	/**
	 * @var Converter\Base\Converter
	 */
	protected $converter;

	public function __construct(\Illuminate\Html\HtmlBuilder $html, \Illuminate\Routing\UrlGenerator $url, $csrfToken, Converter\Base\Converter $converter)
	{
		parent::__construct($html, $url, $csrfToken);
		$this->converter = $converter;
	}

	/**
	 * Set rules for validation
	 *
	 * @param array $rules 		Laravel validation rules
	 */
	public function setValidation($rules)
	{
		$this->converter()->set($rules);
	}

	/**
	 * Get binded converter class
	 *
	 * @return Converter\Base\Converter
	 */
	public function converter()
	{
		return $this->converter;
	}

	/**
	 * Reset validation rules
	 */
	public function resetValidation()
	{
		$this->converter()->reset();
	}

	/**
	 * Opens form, set rules
	 *
	 * @param array $options
	 * @param array $rules 		Laravel validation rules
	 * @return string
	 */
	public function open(array $options = array(), $rules = null)
	{
		$this->setValidation($rules);
		return parent::open($options);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $rules 		Laravel validation rules
	 */
	public function model($model, array $options = array(), $rules = null)
	{
		$this->setValidation($rules);
		return parent::model($model, $options);
	}

	/**
	 * {@inheritdoc}
	 */
	public function input($type, $name, $value = null, $options = array())
	{
		$options += $this->getValidationAttributes($name, $type);
		return parent::input($type, $name, $value, $options);
	}

	/**
	 * {@inheritdoc}
	 */
	public function textarea($name, $value = null, $options = array())
	{
		$options += $this->getValidationAttributes($name);
		return parent::textarea($name, $value, $options);
	}

	/**
	 * {@inheritdoc}
	 */
	public function select($name, $list = array(), $selected = null, $options = array())
	{
		$options += $this->getValidationAttributes($name);
		return parent::select($name, $list, $selected, $options);
	}

	/**
	 * Closes form and reset $this->rules
	 * 
	 * @return string
	 */
	public function close()
	{
		$this->resetValidation();
		return parent::close();
	}

	protected function getValidationAttributes($name, $type = null)
	{
		// raw attribute name without array braces
		if (($i = strpos($name, '[')) !== false)
			$name = substr($name, 0, $i);

		return $this->converter()->convert($name, $type);
	}

}