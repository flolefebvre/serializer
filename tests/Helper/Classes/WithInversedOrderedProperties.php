<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithInversedOrderedProperties extends Serializable
{
    public function __construct(
        public string $b,
        public string $a,
    ) {}
}
