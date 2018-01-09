<?php

namespace Bllim\Laravalid\Converter\Base;

use Illuminate\Support\Str;
use Illuminate\Translation\Translator;

/**
 * Some description...
 * 
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 *
 * @see        Illuminate\Html\FormBuilder
 *
 * @version    0.9
 */
abstract class Message extends Container
{
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get user friendly validation message.
     *
     * @param string $attribute
     * @param string $rule
     * @param array $data
     * @param string $type
     * @return string
     * @see Illuminate\Validation\Validator::getMessage()
     */
    public function getValidationMessage($attribute, $rule, $data = [], $type = null)
    {
        $path = Str::snake($rule);
        if ($type !== null) {
            $path .= '.' . $type;
        }

        if ($this->translator->has('validation.custom.' . $attribute . '.' . $path)) {
            $path = 'custom.' . $attribute . '.' . $path;
        }

        $niceName = $this->getValidationAttribute($attribute);

        return $this->translator->get('validation.' . $path, $data + ['attribute' => $niceName]);
    }

    protected function getValidationAttribute($attribute)
    {
        $niceName = $this->translator->get($langKey = 'validation.attributes.' . $attribute);
        if ($niceName === $langKey) {
            $niceName = str_replace('_', ' ', Str::snake($attribute));
        }

        return $niceName;
    }
}
