<?php

namespace Tests\Helper;

use Flolefebvre\Serializer\Serializable;
use Tests\Helper\Classes\WithArray;

class ClassFactory
{
    private function recursiveMake(int $depth, int $elements): array
    {
        if ($depth <= 0) return [];

        $result = [];
        for ($i = 0; $i < $elements; $i++) {
            $result[] = new WithArray($this->recursiveMake($depth - 1, $elements));
        }

        return $result;
    }

    public function make(int $depth, int $elements): Serializable
    {
        return new WithArray($this->recursiveMake($depth, $elements));
    }
}
