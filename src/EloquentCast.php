<?php

namespace Flolefebvre\Serializer;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EloquentCast implements CastsAttributes
{
    protected CastMode $mode;

    public function __construct(string $mode = CastMode::Single->value)
    {
        $this->mode = CastMode::from($mode);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        return match ($this->mode) {
            CastMode::Single => Serializable::from(json_decode($value, true)),
            CastMode::List => array_map(fn($data) => Serializable::from($data), json_decode($value, true))
        };
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        $data =  match ($this->mode) {
            CastMode::Single => $value->toArray(),
            CastMode::List => array_map(fn($data) => $data->toArray(), $value),
        };
        return json_encode($data);
    }
}
