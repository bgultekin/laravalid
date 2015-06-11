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
	 * Get user friendly validation message
	 *
	 * @return string
	 */
	public static function getValidationMessage($attribute, $rule, $data = [], $type = null)
	{
		$path = $rule;
		if ($type !== null)
		{
			$path .= '.' . $type;
		}

		if (\Lang::has('validation.custom.' . $attribute . '.' . $path))
		{
			$path = 'custom.' . $attribute . '.' . $path;
		}

		$niceName = !\Lang::has('validation.attributes.'.$attribute) ? $attribute : \Lang::get('validation.attributes.'.$attribute);

		return \Lang::get('validation.' . $path, $data + ['attribute' => $niceName]);
	}

	/**
	 * Get the raw attribute name without array braces
	 *
	 * @return string
	 */
	public static function getFormAttribute($name)
	{
		return substr($name, -2) === '[]' ? substr($name, 0, -2) : $name;
	}
}