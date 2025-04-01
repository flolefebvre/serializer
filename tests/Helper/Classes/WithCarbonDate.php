<?php

namespace Tests\Helper\Classes;

use Carbon\Carbon;
use Flolefebvre\Serializer\Serializable;

class WithCarbonDate extends Serializable
{
    public function __construct(
        public Carbon $date
    ) {}
}
