<?php

namespace Tests\Helper\Classes;

class WithOneNotPropertyText
{
    public function __get($name)
    {
        if ($name === 'text') return 'the text';
    }
}
