<?php 
namespace Bllim\Laravalid;

use Illuminate\Routing\Controller as BaseController;
use Bllim\Laravalid\Converter\JqueryValidation\Converter;

class RuleController extends BaseController
{
	public function getIndex($rule)
	{
		return (new Converter())->route()->convert($rule, \Input::all());
	}
}