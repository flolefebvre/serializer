<?php

namespace Flolefebvre\Serializer;

use Carbon\Carbon;
use ErrorException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use ReflectionIntersectionType;
use Illuminate\Http\JsonResponse;
use Flolefebvre\Serializer\Rules\Rule;
use Illuminate\Support\Facades\Validator;
use Flolefebvre\Serializer\Casts\TypeCast;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;
use Flolefebvre\Serializer\Casts\CarbonStringCast;
use Flolefebvre\Serializer\Rules\TypeExtendsClass;
use Flolefebvre\Serializer\Exceptions\MissingPropertyException;
use Flolefebvre\Serializer\Exceptions\TypesDoNotMatchException;
use Flolefebvre\Serializer\Exceptions\UnionTypeCannotBeUnserializedException;
use Flolefebvre\Serializer\Exceptions\IntersectionTypeCannotBeUnserializedException;

abstract class Serializable implements Arrayable, Responsable
{
    private static array $defaultCasts = [
        Carbon::class => CarbonStringCast::class
    ];

    public function toArray(): array
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        $array = [
            '_type' => static::class
        ];

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyValue = $this->$propertyName;

            $typeCast = static::getTypeCast($property);;
            if ($typeCast !== null) {
                $array[$propertyName] = $typeCast->serialize($propertyValue);
                continue;
            }

            if (is_iterable($propertyValue)) {
                $subVars = [];
                foreach ($propertyValue as $key => &$v) {
                    if ($v instanceof Serializable)
                        $subVars[$key] = $v->toArray();
                    else
                        $subVars[$key] = $v;
                }
                $array[$propertyName] = $subVars;
            } elseif (is_object($propertyValue)) {
                $array[$propertyName] = $propertyValue->toArray();
            } else {
                $array[$propertyName] = $propertyValue;
            }
        }

        // Makes sure the return value always has the same order
        // event if the user moves properties around
        ksort($array);
        return  $array;
    }

    private static function getTypeCast(ReflectionParameter|ReflectionProperty $param): ?TypeCast
    {
        $castAttributes = $param->getAttributes(CastTypeWith::class);
        if (count($castAttributes) > 0) {
            $castAttribute = $castAttributes[0]->newInstance();
            return app($castAttribute->class);
        }

        $typeName = $param->getType()?->getName();
        if (isset(static::$defaultCasts[$typeName])) {
            return app(static::$defaultCasts[$typeName]);
        }
        return null;
    }

    private static function makeValidator(array $array, string $prefix = ''): array
    {
        $type = $array['_type'] ?? static::class;

        $validator = [
            $prefix . '_type' => [new TypeExtendsClass(static::class)]
        ];

        $constructor = new ReflectionClass($type)->getConstructor();
        if ($constructor === null) return $validator;

        foreach ($constructor->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            if ($paramType instanceof ReflectionNamedType) {
                $paramTypeName = $paramType->getName();
                $rules = [];
                if ($paramType->allowsNull() || $param->isDefaultValueAvailable()) {
                    $rules[] = 'nullable';
                } else {
                    if ($paramTypeName === 'array') {
                        $rules[] = 'present';
                    } else {
                        $rules[] = 'required';
                    }
                }

                $ruleAttributes = $param->getAttributes(Rule::class);
                $rulesFromAttributes = array_merge(...array_map(fn($r) => $r->newInstance()->toArray(), $ruleAttributes));
                $rules = [...$rules, ...$rulesFromAttributes];

                $typeCast = static::getTypeCast($param);;
                if ($typeCast !== null) {
                    $rules[] = $typeCast->serializedType;
                    $validator[$prefix . $paramName] = $rules;
                    continue;
                }

                if (class_exists($paramTypeName) && isset($array[$paramName])) {
                    $value = $array[$paramName];
                    $validator = [...$validator, ...$paramTypeName::makeValidator($value, $prefix . $paramName . '.')];
                } else {
                    $rules[] = $paramType->getName();
                }

                $validator[$prefix . $paramName] = $rules;

                if ($paramType->getName() === 'array') {
                    $attributes = $param->getAttributes(ArrayType::class);
                    $arrayType = count($attributes) === 0
                        ? Serializable::class
                        : $attributes[0]->newInstance()->type;

                    if ($arrayType == 'mixed') continue;
                    elseif (class_exists($arrayType)) {
                        $value = $array[$paramName] ?? null;
                        $subValidators = [];
                        if (is_array($value) && array_is_list($value)) {
                            foreach ($value as $key => $v) {
                                $v['_type'] ??= $arrayType;
                                $subValidators = [...$subValidators, ...$arrayType::makeValidator($v, $prefix . $paramName . '.' . $key . '.')];
                            }
                        }
                        $validator = [...$validator, ...$subValidators];
                    } else {
                        $subValidators = [$paramName . '.*' => $arrayType];
                        $validator = [...$validator, ...$subValidators];
                    }
                }
            }
        }

        return $validator;
    }

    public static function validate(array $array): void
    {
        $validator = static::makeValidator($array);
        Validator::make($array, $validator)->validate();
    }

    public static function fromRequest(Request $request): static
    {
        $data = $request->all();
        static::validate($data);
        return static::from($data);
    }

    private static function getValueWithName(array|object $input, string $name): mixed
    {
        if (is_array($input)) return $input[$name] ?? null;
        else {
            try {
                return $input->$name;
            } catch (ErrorException) {
                return null;
            }
        }
    }

    private static function getValue(array|object $input, string $name): mixed
    {
        $value = static::getValueWithName($input, $name);
        if ($value !== null) return $value;
        else return static::getValueWithName($input, Str::snake($name));
    }

    public static function from(array|string|object $input): static
    {
        $type = null;

        // Convert to array if not array
        if (is_string($input)) $input = json_decode($input, true);
        if (is_object($input)) {
            $type = get_class($input);
        }

        // Get the right type
        if (!is_subclass_of($type,  static::class)) {
            $type = static::getValue($input, '_type') ?? static::class;
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

            // Find the right way to use the value
            $valueFromInput = static::getValue($input, $name);

            // If we don't have the value, use default value if available or sets null if nullable
            // If not, throw properly (to avoid throwing when trying to instantiate the class later)
            if ($valueFromInput === null) {
                if ($param->isDefaultValueAvailable()) {
                    $params[$name] = $param->getDefaultValue();
                    continue;
                } elseif ($param->allowsNull()) {
                    $params[$name] = null;
                    continue;
                } else {
                    throw new MissingPropertyException($name, $type);
                }
            }

            if ($paramType instanceof ReflectionNamedType) {
                $typeName = $paramType->getName();
                $elementFromArrayType = gettype($valueFromInput);

                $typeCast = static::getTypeCast($param);;
                if ($typeCast !== null) {
                    $params[] = $typeCast->unserialize($valueFromInput);
                    continue;
                }

                if (in_array($typeName, ['bool', 'int', 'float', 'string'])) {
                    if (!static::isSameType($elementFromArrayType, $typeName)) throw new TypesDoNotMatchException($typeName, $elementFromArrayType);
                } elseif ($typeName == 'array') {
                    if ($elementFromArrayType !== 'array') throw new TypesDoNotMatchException($typeName, $elementFromArrayType);

                    $attributes = $param->getAttributes(ArrayType::class);
                    $arrayType = count($attributes) === 0
                        ? Serializable::class
                        : $attributes[0]->newInstance()->type;

                    if ($arrayType !== 'mixed') {
                        if (class_exists($arrayType)) {
                            foreach ($valueFromInput as &$v) {
                                $v = $arrayType::from($v);
                            }
                        } else {
                            foreach ($valueFromInput as &$v) {
                                if (!static::isSameType(gettype($v), $arrayType)) throw new TypesDoNotMatchException($arrayType, gettype($v));
                            }
                        }
                    }
                } elseif (class_exists($typeName)) {
                    $valueFromInput = $typeName::from($valueFromInput);
                }
            }

            $params[] = $valueFromInput;
        }

        return new $type(...$params);
    }

    public function toResponse($request)
    {
        return new JsonResponse(
            data: $this->toArray(),
            status: $request->isMethod(Request::METHOD_POST) ? Response::HTTP_CREATED : Response::HTTP_OK
        );
    }

    public static function collect(iterable $iterable): array
    {
        $result = [];
        foreach ($iterable as $item) {
            $result[] = static::from($item);
        }
        return $result;
    }

    private static function isSameType(string $a, string $b): bool
    {
        return static::normalize($a) === static::normalize($b);
    }

    private static function normalize(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer' => 'int',
            'bool', 'boolean' => 'bool',
            'float', 'double', 'real' => 'float',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            'null' => 'null',
            'mixed' => 'mixed',
            'callable' => 'callable',
            default => $type, // pour les classes, interfaces, etc.
        };
    }
}
