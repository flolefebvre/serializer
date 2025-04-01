<?php

namespace Flolefebvre\Serializer\Casts;

interface TypeCast
{
    public string $serializedType { get; }
    public function serialize(mixed $value): mixed;
    public function unserialize(mixed $value): mixed;
}
