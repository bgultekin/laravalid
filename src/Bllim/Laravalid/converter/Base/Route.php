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

abstract class Route extends Container {

	public function convert($name, $parameters = [])
	{
		if (!is_null($result = parent::convert($name, $parameters)))
			return $result;

		return $this->defaultRoute($name, $parameters);
	}

	protected function defaultRoute($name, $parameters = [])
	{
		$params = empty($parameters['params']) ? []
			: array_map('Bllim\Laravalid\Helper::decrypt', is_array($parameters['params']) ? $parameters['params'] : array($parameters['params']));
		unset($parameters['params'], $parameters['_']);

		$rules = array();
		// allow multiple `remote` rules
		foreach (explode('-', $name) as $i => $rule)
		{
			foreach ($parameters as $k => $v)
				$rules[$k][] = empty($params[$i]) ? $rule : $rule . ':' . $params[$i];
		}

		$validator = \Validator::make($parameters, $rules);

		if (!$validator->fails())
			return \Response::json(true);

		return \Response::json($validator->messages()->first());
	}

}