<?php namespace Bllim\Laravalid\Converter\Base;
/**
 * Some description...
 * 
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

abstract class Rule extends Container {

	public function mergeOutputAttributes(array $outputAttributes, array &$ruleAttributes, $inputType = null)
	{
		return $outputAttributes + $ruleAttributes;
	}

}