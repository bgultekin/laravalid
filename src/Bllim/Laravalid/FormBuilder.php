<?php

namespace Bllim\Laravalid;

use Collective\Html\HtmlBuilder;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;

/**
 * This class is extending \Collective\Html\FormBuilder to make 
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
 * @see        Collective\Html\FormBuilder
 * @version    0.9
 */

class FormBuilder extends \Collective\Html\FormBuilder
{
    protected $converter;

    public function __construct(HtmlBuilder $html, UrlGenerator $url, Factory $view, $csrfToken, Converter\Base\Converter $converter, Request $request = null)
    {
        if (with(new \ReflectionClass('Collective\Html\FormBuilder'))->getConstructor()->getNumberOfParameters() > 3)
            parent::__construct($html, $url, $view, $csrfToken, $request);
        else
            parent::__construct($html, $url, $csrfToken);

        $this->converter = $converter;
    }

    /**
     * Set rules for validation.
     *
     * @param array $rules Laravel validation rules
     * @param string $formName
     */
    public function setValidation($rules, $formName = null)
    {
        $this->converter()->set($rules, $formName);
    }

    /**
     * Get bound converter class.
     *
     * @return Converter\Base\Converter
     */
    public function converter()
    {
        return $this->converter;
    }

    /**
     * Reset validation rules.
     *
     * @param string|bool $formName
     */
    public function resetValidation($formName = false)
    {
        $this->converter()->reset($formName);
    }

    /**
     * Opens form, set rules.
     *
     * @param array $options
     * @param array $rules Laravel validation rules
     * @return \Illuminate\Support\HtmlString
     */
    public function open(array $options = [], $rules = null)
    {
        $this->setValidation($rules, isset($options['name']) ? $options['name'] : null);

        return parent::open($options);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $rules Laravel validation rules
     */
    public function model($model, array $options = [], $rules = null)
    {
        $this->setValidation($rules, isset($options['name']) ? $options['name'] : null);

        return parent::model($model, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function input($type, $name, $value = null, $options = [])
    {
        $options += $this->getValidationAttributes($name, $type);

        return parent::input($type, $name, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function textarea($name, $value = null, $options = [])
    {
        $options += $this->getValidationAttributes($name);

        return parent::textarea($name, $value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function select($name, $list = [], $selected = null, array $selectAttributes = [], array $optionsAttributes = [], array $optgroupsAttributes = [])
    {
        $selectAttributes += $this->getValidationAttributes($name);

        return parent::select($name, $list, $selected, $selectAttributes, $optionsAttributes, $optgroupsAttributes);
    }

    /**
     * Closes form and reset $this->rules.
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
        if (($i = strpos($name, '[')) !== false) {
            $name = substr($name, 0, $i) . preg_replace(['/(?:^|\G)\[\s*([A-Za-z_]\w*)\s*\]\s*/', '/\[.*$/'], ['.$1', ''], substr($name, $i));
        }

        return $this->converter->convert($name, $type);
    }
}
