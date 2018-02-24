<?php namespace Bllim\Laravalid\Converter\Base;

use Illuminate\Support\Facades\Response;

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

	/**
	 * @var \Illuminate\Validation\Factory
	 */
	protected $validatorFactory;

	/**
	 * @var \Illuminate\Encryption\Encrypter
	 */
	protected $encrypter;

	public function __construct($validatorFactory, $encrypter)
	{
		$this->validatorFactory = $validatorFactory;
		$this->encrypter = $encrypter;
	}

	public function convert($name, $parameters = array())
	{
		if (!is_null($result = parent::convert($name, $parameters)))
			return $result;

		return $this->defaultRoute($name, reset($parameters) ?: array());
	}

	protected function defaultRoute($name, $parameters = array())
	{
		$params = $this->decryptParameters($parameters);

		$rules = array();
		// allow multiple `remote` rules
		foreach (explode('-', $name) as $i => $rule)
		{
			foreach ($parameters as $k => $v)
				$rules[$k][] = empty($params[$i]) ? $rule : $rule . ':' . $params[$i];
		}

		$validator = $this->validatorFactory->make($parameters, $rules);

		if (!$validator->fails())
			return Response::json(true);

		return Response::json($validator->messages()->first());
	}

	protected function decryptParameters(array &$parameters)
	{
		$params = empty($parameters['params']) ? array()
			: (is_array($parameters['params']) ? $parameters['params'] : array($parameters['params']));
		unset($parameters['params'], $parameters['_']);

		foreach ($params as &$param) {
			if (!empty($param))
				$param = $this->encrypter->decrypt($param);
		}

		return $params;
	}

}