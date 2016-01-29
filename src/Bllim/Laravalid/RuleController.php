<?php

namespace Bllim\Laravalid;

use Bllim\Laravalid\Converter\JqueryValidation\Converter;
use Illuminate\Routing\Controller as BaseController;

class RuleController extends BaseController
{
    public function getIndex($rule)
    {
        return (new Converter())->route()->convert($rule, \Input::all());
    }
}
