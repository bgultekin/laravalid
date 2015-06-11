<?php namespace Bllim\Laravalid;
/**
 * Helper class
 *
 *
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

class Helper {

	public static function encrypt($data)
	{
		return \Crypt::encrypt($data);
	}

	public static function decrypt($data)
	{
		return \Crypt::decrypt($data);
	}

	/**
	 * Get user friendly attribute name
	 *
	 * @return string
	 */
	public static function getAttributeName($attribute)
	{
		return !\Lang::has('validation.attributes.'.$attribute) ? $attribute : \Lang::get('validation.attributes.'.$attribute);
	}
}