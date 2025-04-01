<?php

namespace Tests\Helper\Classes;

use Carbon\Carbon;
use Flolefebvre\Serializer\Casts\CarbonStringCast;
use Flolefebvre\Serializer\CastTypeWith;
use Flolefebvre\Serializer\Serializable;

class WithCastAttribute extends Serializable
{
    public function __construct(
        #[CastTypeWith(CarbonStringCast::class)]
        public Carbon $date
    ) {}
}
