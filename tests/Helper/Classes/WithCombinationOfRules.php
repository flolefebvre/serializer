<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Rules\Rule;
use Flolefebvre\Serializer\Serializable;

class WithCombinationOfRules extends Serializable
{
    public function __construct(
        #[Rule('min:3|max:5')]
        #[Rule(['email'])]
        public string $text
    ) {}
}
