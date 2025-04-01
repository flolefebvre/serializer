<?php

namespace Flolefebvre\Serializer;

use Attribute;

#[Attribute]
class CastTypeWith
{
    public function __construct(
        public string $class
    ) {}
}
