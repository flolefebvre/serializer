<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithOptionalArray extends Serializable
{
    public function __construct(
        #[ArrayType('string')]
        public ?array $class
    ) {}
}
