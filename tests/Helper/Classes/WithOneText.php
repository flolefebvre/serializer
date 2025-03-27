<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithOneText extends Serializable
{
    public function __construct(public string $text) {}
}
