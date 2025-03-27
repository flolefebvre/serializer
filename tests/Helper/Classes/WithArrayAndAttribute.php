<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\ArrayType;
use Flolefebvre\Serializer\Serializable;

class WithArrayAndAttribute extends Serializable
{
    public function __construct(
        #[ArrayType(WithOneText::class)]
        public array $array
    ) {}
}
