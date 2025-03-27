<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithDefaultValues extends Serializable
{
    public function __construct(
        public string $a,
        public string $b = 'salut'
    ) {}
}
