<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithSubClass extends Serializable
{
    public function __construct(public WithOneText $subClass) {}
}
