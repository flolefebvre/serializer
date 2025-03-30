<?php

namespace Flolefebvre\Serializer\Exceptions;

class ArrayTypeIsMissingException extends UnserializeException
{
    public function __construct(string $class)
    {
        parent::__construct(message: $class . ' is missing ArrayType');
    }
}
