<?php

namespace Flolefebvre\Serializer\Exceptions;

class TypesDoNotMatchException extends UnserializeException
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct($expected . ' expected, ' . $actual . ' was given');
    }
}
