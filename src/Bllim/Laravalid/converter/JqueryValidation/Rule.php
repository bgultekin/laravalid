<?php namespace Bllim\Laravalid\Converter\JqueryValidation;

class Rule extends \Bllim\Laravalid\Converter\Base\Rule {

	public function email()
	{
		return array('data-rule-email' => 'true');
	}

	public function required()
	{
		return array('required' => 'required');
	}

	public function url()
	{
		return array('data-rule-url' => 'true');
	}

	public function integer()
	{
		return array('data-rule-integer' => 'true');
	}

	public function numeric()
	{
		return array('data-rule-number' => 'true');
	}

	public function ip()
	{
		return array('data-rule-ipv4' => 'true');
	}

	public function same($parsedRule)
	{
		$value = vsprintf(':input[name=\'%1$s\']', $parsedRule['parameters']);
		return array('data-rule-equalto' => $value);
	}

	public function different($parsedRule)
	{
		$value = vsprintf(':input[name=\'%1$s\']', $parsedRule['parameters']);
		return array('data-rule-notequalto' => $value);
	}

	public function regex($parsedRule)
	{
		$rule = reset($parsedRule['parameters']);

		if (substr($rule, 0, 1) == substr($rule, -1, 1)) {
			$rule = substr($rule, 1, -1);
		}

		return array('pattern' => $rule);
	}

	public function alpha()
	{
		return array('pattern' => '^[A-Za-z_.-]+$');
	}

	public function alpha_num()
	{
		return array('pattern' => '^[A-Za-z0-9_.-]+$');
	}

	public function image()
	{
		return array('accept' => 'image/*');
	}

	public function date()
	{
		return array('data-rule-date' => 'true');
	}

	public function before($parsedRule)
	{
		return array('max' => reset($parsedRule['parameters']));
	}

	public function after($parsedRule)
	{
		return array('min' => reset($parsedRule['parameters']));
	}

	/**
	 * Rules conversion which return attributes as an array
	 *
	 * @param  array $parsedRule ['name' => '', 'parameters' => []]
	 * @param  string $attribute
	 * @param  string $type Type of input
	 * @return  array
	 */
	public function min($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return array('min' => reset($parsedRule['parameters']));
				break;
			
			default:
				return array('minlength' => reset($parsedRule['parameters']));
				break;
		}
	}

	public function max($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return array('max' => reset($parsedRule['parameters']));
				break;
			
			default:
				return array('maxlength' => reset($parsedRule['parameters']));
				break;
		}
	}

	public function between($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return array('data-rule-range' => vsprintf('%1$s,%2$s', $parsedRule['parameters']));
				break;
			
			default:
				return array('data-rule-rangelength' => vsprintf('%1$s,%2$s', $parsedRule['parameters']), 'maxlength' => vsprintf('%2$s', $parsedRule['parameters']));
				break;
		}
	}

	protected function remote($method, $parsedRule)
	{
		$param = implode(',', $parsedRule['parameters']);
		$encryptedParam = empty($param) ? '' : $this->encrypter->encrypt($param);

		return array('data-rule-remote' => $this->routeUrl . '/' . $method . '?params=' . $encryptedParam);
	}

	public function unique($parsedRule)
	{
		return $this->remote(__FUNCTION__, $parsedRule);
	}

	public function exists($parsedRule)
	{
		return $this->remote(__FUNCTION__, $parsedRule);
	}

	public function required_with($parsedRule)
	{
		$value = ':input:enabled[name=\''
			. implode('\']:not(:checkbox):not(:radio):filled,:input:enabled[name=\'', $parsedRule['parameters'])
			. '\']:not(:checkbox):not(:radio):filled,input:enabled[name=\''
			. implode('\']:checked,input:enabled[name=\'', $parsedRule['parameters']) . '\']:checked';

		return array('data-rule-required' => $value);
	}

	public function required_without($parsedRule)
	{
		$value = ':input:enabled[name=\''
			. implode('\']:not(:checkbox):not(:radio):blank,:input:enabled[name=\'', $parsedRule['parameters'])
			. '\']:not(:checkbox):not(:radio):blank,input:enabled[name=\''
			. implode('\']:unchecked,input:enabled[name=\'', $parsedRule['parameters']) . '\']:unchecked';

		return array('data-rule-required' => $value);
	}

	public function active_url($parsedRule)
	{
		return $this->remote(__FUNCTION__, $parsedRule);
	}

	public function mimes($parsedRule)
	{
		// TODO: detect mime-type from extensions then sort and group by
		return array('accept' => '.' . implode(',.', $parsedRule['parameters']));
	}

	public function mergeOutputAttributes(array $outputAttributes, array &$ruleAttributes, $inputType = null)
	{
		// try to merge `remote` rules
		if (isset($outputAttributes['data-rule-remote']) && isset($ruleAttributes['data-rule-remote']))
		{
			$rule = $outputAttributes['data-rule-remote'];
			$rule = ($rule[0] == '{' && substr($rule, -1) == '}') ? json_decode($rule, true) : array('url' => $rule);

			$mRule = $ruleAttributes['data-rule-remote'];
			$mRule = ($mRule[0] == '{' && substr($mRule, -1) == '}') ? json_decode($mRule, true) : array('url' => $mRule);

			$regex = (preg_match('/^\w/', $this->routeUrl) ? '#\b' : '#') . preg_quote($this->routeUrl, '#') . '/([\w-]+)(\?.*)?$#i';
			if (preg_match($regex, $mRule['url'], $mm) && preg_match($regex, $rule['url'], $m, PREG_OFFSET_CAPTURE)) {
				// merge callback URLs
				$query = empty($m[2][0]) ? (empty($mm[2]) ? '' : $mm[2]) : $m[2][0] . (empty($mm[2]) ? '' : '&' . substr($mm[2], 1));
				$rule['url'] = substr($rule['url'], 0, $m[1][1]) . $m[1][0] . '-' . $mm[1] . str_replace('params=', 'params[]=', $query);

				// merge data of `remote` rules
				if (isset($mRule['data']))
					$rule['data'] = isset($rule['data']) ? ($rule['data'] + $mRule['data']) : $mRule['data'];

				$outputAttributes['data-rule-remote'] = empty($rule['data']) ? $rule['url'] : str_replace('\/', '/', json_encode($rule));
				unset($outputAttributes['data-msg-remote'], $ruleAttributes['data-rule-remote']);
			}
		}

		$outputAttributes = parent::mergeOutputAttributes($outputAttributes, $ruleAttributes, $inputType);

		// remove duplicated rule attributes
		if (!empty($inputType) && isset($ruleAttributes[$k = 'data-rule-' . $inputType]) && strcasecmp('true', $ruleAttributes[$k]) == 0)
			unset($outputAttributes[$k]);

		return $outputAttributes;
	}

}