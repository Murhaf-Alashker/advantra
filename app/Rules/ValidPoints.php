<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPoints implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validPoints = range(10, 1000, 10); // كل 10 من 10 إلى 1000

        if (!in_array($value, $validPoints)) {
            $fail(__('validation.valid_points', ['attribute' => $attribute]));
        }

    }
}
