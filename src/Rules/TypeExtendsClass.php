<?php

namespace Flolefebvre\Serializer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TypeExtendsClass implements ValidationRule
{
    public function __construct(public string $class) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== $this->class && !is_subclass_of($value, $this->class)) {
            $fail('The :input must be the class or a subclass of ' . $this->class);
        }
    }
}
