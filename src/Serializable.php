<?php

namespace Flolefebvre\Serializer;

use Flolefebvre\Serializer\Exceptions\ArrayTypeIsMissingException;
use Flolefebvre\Serializer\Exceptions\IntersectionTypeCannotBeUnserializedException;
use Flolefebvre\Serializer\Exceptions\MissingPropertyException;
use Flolefebvre\Serializer\Exceptions\TypesDoNotMatchException;
use Flolefebvre\Serializer\Exceptions\UnionTypeCannotBeUnserializedException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;

abstract class Serializable
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

    public static function from(array|string|object $input): static
    {
        $type = null;

        // Convert to array if not array
        if (is_string($input)) $input = json_decode($input, true);
        if (is_object($input)) {
            $type = get_class($input);
            $input = get_object_vars($input);
        }

        // Get the right type
        if (!is_subclass_of($type,  static::class)) {
            $type = $input['_type'] ?? static::class;
        }

        $constructor = new ReflectionClass($type)->getConstructor();
        if ($constructor === null) return new $type();

        // The params that will be used in the constructor
        $params = [];

        // Loop on the constructor parameters and get the value in $params
        $constructorParameters = $constructor->getParameters();
        foreach ($constructorParameters as &$param) {
            $name = $param->getName();
            $paramType = $param->getType(); // ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null

            // We don't work with Union and Intersection types
            if ($paramType instanceof ReflectionIntersectionType) {
                throw new IntersectionTypeCannotBeUnserializedException();
            } elseif ($paramType instanceof ReflectionUnionType) {
                throw new UnionTypeCannotBeUnserializedException();
            }

            // If we don't have the value, use default value if available
            // If not, throw properly (to avoid throwing when trying to instantiate the class later)
            if (!isset($input[$name])) {
                if ($param->isDefaultValueAvailable()) {
                    $params[$name] = $param->getDefaultValue();
                    continue;
                } else {
                    throw new MissingPropertyException();
                }
            }

            // Find the right way to use the value
            $elementFromArray = $input[$name];

            if ($paramType instanceof ReflectionNamedType) {
                $typeName = $paramType->getName();
                $elementFromArrayType = gettype($elementFromArray);

                if (in_array($typeName, ['bool', 'int', 'float', 'string'])) {
                    if ($elementFromArrayType !== $typeName) throw new TypesDoNotMatchException();
                } elseif ($typeName == 'array') {
                    if ($elementFromArrayType !== 'array') throw new TypesDoNotMatchException();

                    $attributes = $param->getAttributes(ArrayType::class);
                    if (count($attributes) !== 1) throw new ArrayTypeIsMissingException();
                    $arrayType = $attributes[0]->newInstance()->type;

                    foreach ($elementFromArray as &$v) {
                        $v = $arrayType::from($v);
                    }
                } elseif (class_exists($typeName)) {
                    $elementFromArray = $typeName::from($elementFromArray);
                }
            }

            $params[] = $elementFromArray;
        }

        return new $type(...$params);
    }
}
