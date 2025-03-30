<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArrayOfSubClass extends Serializable
{
    public function __construct(
        #[ArrayType(WithSubClass::class)]
        public array $array
    ) {}
}
