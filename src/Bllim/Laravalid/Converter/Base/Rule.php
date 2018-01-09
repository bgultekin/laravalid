<?php

namespace Bllim\Laravalid\Converter\Base;

use Illuminate\Contracts\Encryption\Encrypter;

/**
 * Some description...
 * 
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 *
 * @see        Illuminate\Html\FormBuilder
 *
 * @version    0.9
 */
abstract class Rule extends Container
{
    /**
     * @var string
     */
    protected $routeUrl;

    protected $encrypter;

    public function __construct($routeUrl = '/laravalid', Encrypter $encrypter = null)
    {
        $this->routeUrl = $routeUrl;
        $this->encrypter = $encrypter;
    }

    public function mergeOutputAttributes(array $outputAttributes, array &$ruleAttributes, $inputType = null)
    {
        return $outputAttributes + $ruleAttributes;
    }
}
