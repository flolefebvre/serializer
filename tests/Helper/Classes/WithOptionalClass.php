<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithOptionalClass extends Serializable
{
    public function __construct(public ?WithOneText $class) {}
}
