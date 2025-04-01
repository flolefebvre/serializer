<?php

namespace Flolefebvre\Serializer\Casts;

use Carbon\Carbon;
use InvalidArgumentException;

class CarbonStringCast implements TypeCast
{
    public string $serializedType = 'string';

    public function serialize(mixed $value): string
    {
        if (!($value instanceof Carbon)) throw new InvalidArgumentException('value must be of type Carbon');

        return $value->toIso8601String();
    }

    public function unserialize(mixed $value): Carbon
    {
        if (gettype($value) !== 'string') throw new InvalidArgumentException('$value must be of type string');

        return Carbon::parse($value);
    }
}
