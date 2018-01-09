<?php

namespace Bllim\Laravalid\Converter\Base;

/**
 * Some description...
 * 
 * @package    Laravel Validation For Client-Side
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */

abstract class Route extends Container
{
    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validator;

    /**
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    public function __construct($validator, $response, $encrypter = null)
    {
        $this->validator = $validator;
        $this->response = $response;
        $this->encrypter = $encrypter;
    }

    public function convert($name, $parameters = [])
    {
        if ($result = parent::convert($name, $parameters))
            return $result;

        return $this->defaultRoute($name, reset($parameters) ?: []);
    }

    protected function defaultRoute($name, $parameters = [])
    {
        $params = $this->decryptParameters($parameters);

        $rules = [];
        // allow multiple `remote` rules
        foreach (explode('-', $name) as $i => $rule)
        {
            foreach ($parameters as $k => $v)
                $rules[$k][] = empty($params[$i]) ? $rule : $rule . ':' . $params[$i];
        }

        $validator = $this->validator->make($parameters, $rules);

        if (!$validator->fails()) {
            return $this->response->json(true);
        }

        return $this->response->json($validator->messages()->first());
    }

    protected function decryptParameters(array &$parameters)
    {
        $params = empty($parameters['params']) ? []
            : (is_array($parameters['params']) ? $parameters['params'] : array($parameters['params']));
        unset($parameters['params'], $parameters['_']);

        if (isset($this->encrypter)) {
            foreach ($params as &$param) {
                if (!empty($param))
                    $param = $this->encrypter->decrypt($param);
            }
        }

        return $params;
    }
}
