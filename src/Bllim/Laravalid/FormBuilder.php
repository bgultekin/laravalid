<?php namespace Bllim\Laravalid;
/**
 * This class is extending \Collective\Html\FormBuilder to make 
 * validation easy for both client and server side. Package convert 
 * laravel validation rules to javascript validation plugins while 
 * using laravel FormBuilder.
 *
 * USAGE: Just pass $rules to Form::open($options, $rules) and use.
 * You can also pass by using Form::setValidation from controller or router
 * for coming first form::open.
 * When Form::close() is used, $rules are reset.
 *
 * NOTE: If you use min, max, size, between and type of input is different from string
 * don't forget to specify the type (by using numeric, integer).
 *
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Collective\Html\FormBuilder
 * @version    0.9
 */
use Lang;

class FormBuilder extends \Collective\Html\FormBuilder {

	protected $converter;

	public function __construct(\Collective\Html\HtmlBuilder $html, \Illuminate\Routing\UrlGenerator $url, $csrfToken, Converter\Base\Converter $converter)
	{
		parent::__construct($html, $url, $csrfToken);
		$plugin = \Config::get('laravalid.plugin');
		$this->converter = $converter;
	}

	/**
	 * Set rules for validation
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function setValidation($rules, $formName = null)
	{
		$this->converter()->set($rules, $formName);
	}

	/**
	 * Get binded converter class
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function converter()
	{
		return $this->converter;
	}

	/**
	 * Reset validation rules
	 *
	 */
	public function resetValidation()
	{
		$this->converter()->reset();
	}

	/**
	 * Opens form, set rules
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 * @see Illuminate\Html\FormBuilder
	 */
	public function open(array $options = array(), $rules = null)
	{
		$this->setValidation($rules);

		if(isset($options['name']))
		{
			$this->converter->setFormName($options['name']);
		}
		else
		{
			$this->converter->setFormName(null);
		}
		
		return parent::open($options);
	}

	/**
	 * Create a new model based form builder.
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 * @see Illuminate\Html\FormBuilder
	 */
	public function model($model, array $options = array(), $rules = null)
	{
		$this->setValidation($rules);
		return parent::model($model, $options);
	}

	/**
	 * @see Illuminate\Html\FormBuilder
	 */
	public function input($type, $name, $value = null, $options = [])
	{
		$options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
		return parent::input($type, $name, $value, $options);
	}

	/**
	 * @see Illuminate\Html\FormBuilder
	 */
	public function textarea($name, $value = null, $options = [])
	{
		$options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
		return parent::textarea($name, $value, $options);
	}

	/**
	 * @see Illuminate\Html\FormBuilder
	 */
	public function select($name, $list = [], $selected = null, $options = [])
	{
		$options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
		return parent::select($name, $list, $selected, $options);
	}

	protected function checkable($type, $name, $value, $checked, $options)
	{
		$options = $this->converter->convert(Helper::getFormAttribute($name)) + $options;
		return parent::checkable($type, $name, $value, $checked, $options);
	}

	/**
	 * Closes form and reset $this->rules
	 * 
	 * @see Illuminate\Html\FormBuilder
	 */
	public function close()
	{
		$this->resetValidation();
		return parent::close();
	}


}