<?php 
namespace Bllim\Laravalid;

use Illuminate\Routing\Controller as BaseController;
use Bllim\Laravalid\Converter\JqueryValidation\Converter;
use Illuminate\Http\Request;

class RuleController extends BaseController
{
	public function getIndex(Request $request, $rule)
	{
		return (new Converter())->route()->convert($rule, $request->all());
	}
}