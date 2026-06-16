<?php

namespace Keystone\Toolkit\Rules;

use Illuminate\Contracts\Validation\Rule;

class CustomRule implements Rule
{
    public function passes($attribute, $value)
    {
        // Your validation logic here
        return $value == 'custom_value';
    }
    public function message()
    {
        return 'The validation failed for this custom rule.';
    }
}
