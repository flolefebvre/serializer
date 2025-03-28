<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithOptionalValue extends Serializable
{
    public function __construct(public ?string $text) {}
}
