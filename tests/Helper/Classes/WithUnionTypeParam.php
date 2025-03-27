<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithUnionTypeParam extends Serializable
{
    public function __construct(public string|int $param) {}
}
