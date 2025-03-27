<?php

namespace Flolefebvre\Serializer;

class Serializable
{
    public function toArray(): array
    {
        $vars = get_object_vars($this);
        $vars['_type'] = static::class;
        ksort($vars);
        foreach ($vars as &$value) {
            if (is_iterable($value)) {
                foreach ($value as &$v) {
                    if ($v instanceof Serializable)
                        $v = $v->toArray();
                }
            } elseif (is_object($value)) {
                $value = $value->toArray();
            }
        }
        return  $vars;
    }

    public static function from(array $array): static
    {
        $type = $array['_type'] ?? static::class;
        unset($array['_type']);

        foreach ($array as &$value) {
            if (is_array($value)) {
                if (isset($value['_type'])) {
                    $value = $value['_type']::from($value);
                } else {
                    foreach ($value as &$v) {
                        if (is_array($v) && isset($v['_type'])) {
                            $v = $v['_type']::from($v);
                        }
                    }
                }
            }
        }

        return new $type(...$array);
    }
}
