<?php

namespace Flolefebvre\Serializer\Exceptions;

class MissingPropertyException extends UnserializeException
{
    public function __construct(string $propertyName, string $type)
    {
        parent::__construct('Cannot find ' . $propertyName . ' for ' . $type);
    }
}
