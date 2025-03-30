<?php

namespace Flolefebvre\Serializer\Rules;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Rule
{
    public function __construct(public string|array $rules) {}

    public function toArray(): array
    {
        if (is_array($this->rules)) return $this->rules;
        else return explode('|', $this->rules);
    }
}
