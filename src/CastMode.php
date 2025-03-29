<?php

namespace Flolefebvre\Serializer;

enum CastMode: string
{
    case Single = 'single';
    case List = 'list';
}
