<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Rules\Rule;
use Flolefebvre\Serializer\Serializable;

class WithAttributeRule extends Serializable
{
    public function __construct(
        #[Rule('min:3')]
        public string $text
    ) {}
}
