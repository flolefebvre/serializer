<?php

namespace Tests\Helper\Models;

use Flolefebvre\Serializer\SerizalizableCast;
use Illuminate\Database\Eloquent\Model;

class Single extends Model
{
    public $timestamps = false;
    public $guarded = [];
    public $table = 'test_table';

    public function casts()
    {
        return [
            'value' => SerizalizableCast::class
        ];
    }
}
