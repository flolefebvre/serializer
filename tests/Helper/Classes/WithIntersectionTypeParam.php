<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithIntersectionTypeParam extends Serializable
{
    public function __construct(public \Iterator&\Countable $param) {}
}
