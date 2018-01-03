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
		return empty($data) ? $data : \Crypt::encrypt($data);
	}

	public static function decrypt($data)
	{
		return empty($data) ? $data : \Crypt::decrypt($data);
	}

	/**
	 * Get user friendly validation message
	 *
	 * @param string $attribute
	 * @param string $rule
	 * @param array $data
	 * @param string $type
	 * @return string
	 * @see Illuminate\Validation\Validator::getMessage()
	 */
	public static function getValidationMessage($attribute, $rule, $data = [], $type = null)
	{
		$path = snake_case($rule);
		if ($type !== null)
		{
			$path .= '.' . $type;
		}

		if (\Lang::has('validation.custom.' . $attribute . '.' . $path))
		{
			$path = 'custom.' . $attribute . '.' . $path;
		}

		$niceName = \Lang::get($langKey = 'validation.attributes.' . $attribute);
		if ($niceName === $langKey) {
			$niceName = str_replace('_', ' ', snake_case($attribute));
		}

		return \Lang::get('validation.' . $path, $data + ['attribute' => $niceName]);
	}

	/**
	 * Get the raw attribute name without array braces
	 *
	 * @param string
	 * @return string
	 */
	public static function getFormAttribute($name)
	{
		return ($i = strpos($name, '[')) === false ? $name : substr($name, 0, $i);
	}

}