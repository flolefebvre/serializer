<?php

namespace Tests\Helper\Classes;

use Carbon\Carbon;
use Flolefebvre\Serializer\Serializable;

class WithCamelCase extends Serializable
{
    public function __construct(
        public Carbon $createdAt
    ) {}
}
