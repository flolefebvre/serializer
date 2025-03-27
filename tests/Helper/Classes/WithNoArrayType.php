<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithNoArrayType extends Serializable
{
    public function __construct(
        public array $array
    ) {}
}
