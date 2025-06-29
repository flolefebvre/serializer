<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithArrayOfSubClassButNoArrayType extends Serializable
{
    public function __construct(
        public array $array
    ) {}
}
