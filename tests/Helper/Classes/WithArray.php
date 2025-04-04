<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArray extends Serializable
{
    public function __construct(
        #[ArrayType(Serializable::class)]
        public array $array
    ) {}
}
