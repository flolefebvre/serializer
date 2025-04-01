<?php

namespace Tests\Helper\Classes;

use Carbon\Carbon;

class WithSnakeCase
{
    public function __construct(
        public Carbon $created_at,
    ) {}
}
