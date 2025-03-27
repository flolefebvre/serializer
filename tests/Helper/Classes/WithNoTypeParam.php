<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithNoTypeParam extends Serializable
{
    public function __construct(public $param) {}
}
