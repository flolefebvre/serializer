<?php

namespace Flolefebvre\Serializer;

use Attribute;

#[Attribute]
class ArrayType
{
    /**
     * @param string $type type of the elements in the array, can be scalar or class 
     */
    public function __construct(
        public string $type
    ) {}
}
