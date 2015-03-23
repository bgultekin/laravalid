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

}