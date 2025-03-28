<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithOneInt extends Serializable
{
    public function __construct(public int $number) {}
}
