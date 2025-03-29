<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArrayOfStrings extends Serializable
{
    public function __construct(
        #[ArrayType('string')]
        public array $array
    ) {}
}
