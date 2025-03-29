<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArrayOfMixed extends Serializable
{
    public function __construct(
        #[ArrayType('mixed')]
        public array $array
    ) {}
}
