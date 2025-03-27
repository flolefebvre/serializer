<?php

namespace Flolefebvre\Serializer;

use Flolefebvre\Serializer\Exceptions\MissingPropertyException;
use ReflectionClass;

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

    public static function from(array|string|object $array): static
    {
        // Convert to array if not array
        if (is_string($array)) $array = json_decode($array, true);
        if (is_object($array)) $array = get_object_vars($array);

        // Get the right type
        $type = $array['_type'] ?? static::class;

        $constructor = new ReflectionClass($type)->getConstructor();
        if ($constructor === null) return new $type();

        // The params that will be used in the constructor
        $params = [];

        // Loop on the constructor parameters and get the value in $params
        $constructorParameters = $constructor->getParameters();
        foreach ($constructorParameters as &$param) {
            $name = $param->getName();

            // If we don't have the value, use default value if available
            // If not, throw properly (to avoid throwing when trying to instantiate the class later)
            if (!isset($array[$name])) {
                if ($param->isDefaultValueAvailable()) {
                    $params[$name] = $param->getDefaultValue();
                    continue;
                } else
                    throw new MissingPropertyException();
            }

            // Find the right way to use the value
            $elementFromArray = $array[$name];
            if (is_array($elementFromArray)) {
                if (isset($elementFromArray['_type'])) {
                    $elementFromArray = $elementFromArray['_type']::from($elementFromArray);
                } else {
                    foreach ($elementFromArray as &$v) {
                        if (is_array($v) && isset($v['_type'])) {
                            $v = $v['_type']::from($v);
                        }
                    }
                }
            }
            $params[] = $elementFromArray;
        }

        return new $type(...$params);
    }
}
