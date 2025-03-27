<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithArray extends Serializable
{
    public function __construct(public array $array) {}
}
