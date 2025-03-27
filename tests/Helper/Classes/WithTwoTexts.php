<?php

namespace Tests\Helper\Classes;

use Flolefebvre\Serializer\Serializable;

class WithTwoTexts extends Serializable
{
    public function __construct(public string $text, public string $text2) {}
}
