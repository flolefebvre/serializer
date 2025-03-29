<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArrayOfArrays extends Serializable
{
    public function __construct(
        #[ArrayType('array')]
        public array $array
    ) {}
}
