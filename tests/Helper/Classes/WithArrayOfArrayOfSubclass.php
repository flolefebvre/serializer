<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArrayOfArrayOfSubclass extends Serializable
{
    public function __construct(
        #[ArrayType(WithArrayOfSubClass::class)]
        public array $array
    ) {}
}
