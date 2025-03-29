<?php

namespace Flolefebvre\Serializer;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Illuminate\Http\Request;
use ReflectionIntersectionType;
use Flolefebvre\Serializer\Rules\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Arrayable;
use Flolefebvre\Serializer\rules\TypeExtendsClass;
use Flolefebvre\Serializer\Exceptions\MissingPropertyException;
use Flolefebvre\Serializer\Exceptions\TypesDoNotMatchException;
use Flolefebvre\Serializer\Exceptions\ArrayTypeIsMissingException;
use Flolefebvre\Serializer\Exceptions\UnionTypeCannotBeUnserializedException;
use Flolefebvre\Serializer\Exceptions\IntersectionTypeCannotBeUnserializedException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class Serializable implements Arrayable, Responsable
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

    private static function makeValidator(array $array, string $prefix = ''): array
    {
        $type = $array['_type'] ?? static::class;

        $validator = [
            $prefix . '_type' => [new TypeExtendsClass(static::class)]
        ];

        $constructor = new ReflectionClass($type)->getConstructor();
        if ($constructor === null) return $validator;

        foreach ($constructor->getParameters() as $param) {
            $paramType = $param->getType();
            $paramName = $param->getName();

            if ($paramType instanceof ReflectionNamedType) {
                $rules = [$paramType->getName()];
                if (!$paramType->allowsNull() && !$param->isDefaultValueAvailable()) {
                    if ($paramType->getName() === 'array') {
                        $rules[] = 'present';
                    } else {
                        $rules[] = 'required';
                    }
                }
                $ruleAttributes = $param->getAttributes(Rule::class);
                $rulesFromAttributes = array_merge(...array_map(fn($r) => $r->newInstance()->toArray(), $ruleAttributes));
                $rules = [...$rules, ...$rulesFromAttributes];

                $validator[$prefix . $paramName] = $rules;

                if ($paramType->getName() === 'array') {
                    $attributes = $param->getAttributes(ArrayType::class);
                    if (count($attributes) !== 1) throw new ArrayTypeIsMissingException();
                    $arrayType = $attributes[0]->newInstance()->type;

                    $value = $array[$paramName] ?? null;
                    $subValidators = [];
                    if (is_array($value) && array_is_list($value)) {
                        foreach ($value as $key => $v) {
                            $v['_type'] ??= $arrayType;
                            $subValidators = [...$subValidators, ...$arrayType::makeValidator($v, $paramName . '.' . $key . '.')];
                        }
                    }

                    $validator = [...$validator, ...$subValidators];
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
        return static::from($request->all());
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

    public function toResponse($request)
    {
        return new JsonResponse(
            data: $this->toArray(),
            status: $request->isMethod(Request::METHOD_POST) ? Response::HTTP_CREATED : Response::HTTP_OK
        );
    }
}
