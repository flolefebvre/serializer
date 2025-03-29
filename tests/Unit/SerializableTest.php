<?php

use Illuminate\Http\Request;
use Tests\Helper\ClassFactory;
use Illuminate\Http\JsonResponse;
use Tests\Helper\Classes\WithArray;
use Tests\Helper\Classes\EmptyClass;
use Tests\Helper\Classes\WithOneInt;
use Tests\Helper\Classes\WithOneText;
use Tests\Helper\Classes\WithSubClass;
use Tests\Helper\Classes\WithTwoTexts;
use Flolefebvre\Serializer\Serializable;
use Tests\Helper\Classes\WithNoArrayType;
use Tests\Helper\Classes\WithNoTypeParam;
use Tests\Helper\Classes\WithArrayOfMixed;
use Tests\Helper\Classes\ChildOfEmptyClass;
use Tests\Helper\Classes\WithArrayOfArrays;
use Tests\Helper\Classes\WithAttributeRule;
use Tests\Helper\Classes\WithDefaultValues;
use Tests\Helper\Classes\WithOptionalValue;
use Tests\Helper\Classes\ChildOfWithOneText;
use Tests\Helper\Classes\WithArrayOfStrings;
use Tests\Helper\Classes\WithUnionTypeParam;
use Illuminate\Validation\ValidationException;
use Pest\Mutate\Mutators\Array\UnwrapArrayMap;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\Classes\WithArrayAndAttribute;
use Tests\Helper\Classes\WithCombinationOfRules;
use Tests\Helper\Classes\WithIntersectionTypeParam;
use Flolefebvre\Serializer\Exceptions\MissingPropertyException;
use Flolefebvre\Serializer\Exceptions\TypesDoNotMatchException;
use Flolefebvre\Serializer\Exceptions\ArrayTypeIsMissingException;
use Flolefebvre\Serializer\Exceptions\UnionTypeCannotBeUnserializedException;
use Flolefebvre\Serializer\Exceptions\IntersectionTypeCannotBeUnserializedException;

describe('#toArray', function () {
    it('converts objects', function (Serializable $object, array $expected) {
        // Act
        $result = $object->toArray();

        // Assert
        expect($result)->toBe($expected);
    })->with([
        'EmptyClass' => [new EmptyClass(), ['_type' => EmptyClass::class]],
        'WithOneText' => [new WithOneText('the text'), ['_type' => WithOneText::class, 'text' => 'the text']],
        'WithSubClass' => [new WithSubClass(new WithOneText('the text')), [
            '_type' => WithSubClass::class,
            'subClass' => ['_type' => WithOneText::class, 'text' => 'the text']
        ]],
        'WithArray' => [new WithArray([new EmptyClass, new WithOneText('the text')]), [
            '_type' => WithArray::class,
            'array' => [
                ['_type' => EmptyClass::class],
                ['_type' => WithOneText::class, 'text' => 'the text']
            ]
        ]],
        'WithArrayOfStrings' => [new WithArrayOfStrings(['a', 'b']), ['_type' => WithArrayOfStrings::class, 'array' => ['a', 'b']]],
        'WithArrayOfArrays' => [new WithArrayOfArrays([[], ['b' => 'c']]), ['_type' => WithArrayOfArrays::class, 'array' => [[], ['b' => 'c']]]],
        'WithArrayOfMixed' => [new WithArrayOfMixed(['a', ['b' => 'c']]), ['_type' => WithArrayOfMixed::class, 'array' => ['a', ['b' => 'c']]]]
    ]);

    it('is fast', function (int $n) {
        // Arrange
        $n = (int)(log(2 * $n + 1, 3) - 1);
        $withArray = new ClassFactory()->make($n, 3);

        // Act
        $before = microtime(true);
        $withArray->toArray();
        $ellapsed = microtime(true) - $before;

        // Assert
        expect($ellapsed)->toBeLessThan(1);
    })->with([
        [100],
        [1000],
        [10000],
        [50000],
    ]);
});

describe('#from', function () {
    it('unserializes from array (from toArray())', function (Serializable $input) {
        // Arrange
        $array = $input->toArray();

        // Act
        $result = Serializable::from($array);

        // Assert
        expect($result)->toEqual($input);
    })->with([
        'EmptyClass' => [new EmptyClass()],
        'WithOneText' => [new WithOneText('the text')],
        'WithSubClass' => [new WithSubClass(new WithOneText('the text'))],
        'WithArray' => [new WithArray([new EmptyClass, new WithOneText('the text')])],
        'WithDefaultValues' => [new WithDefaultValues('a')],
        'WithNoTypeParam' => [new WithNoTypeParam('the text'),],
        'WithArrayOfStrings' => [new WithArrayOfStrings(['a', 'b'])],
        'WithArrayOfArrays' => [new WithArrayOfArrays([[], ['b' => 'c']])],
        'WithArrayOfMixed' => [new WithArrayOfMixed(['a', 5, ['b' => 'c']])],
    ]);

    it('unserializes from array with additionnal data', function (Serializable $input, array $array) {
        // Act
        $result = Serializable::from($array);

        // Assert
        expect($result)->toEqual($input);
    })->with([
        'EmptyClass' => [new EmptyClass(), ['_type' => EmptyClass::class, 'added param' => 'random']],
        'WithOneText' => [new WithOneText('the text'), ['_type' => WithOneText::class, 'text' => 'the text', 'added param' => 'random']],
        'WithSubClass' => [new WithSubClass(new WithOneText('the text')), [
            '_type' => WithSubClass::class,
            'subClass' => ['_type' => WithOneText::class, 'text' => 'the text', 'added param' => 'random']
        ]],
        'WithArray' => [new WithArray([new EmptyClass, new WithOneText('the text')]), [
            '_type' => WithArray::class,
            'array' => [
                ['_type' => EmptyClass::class, 'added param' => 'random'],
                ['_type' => WithOneText::class, 'text' => 'the text', 'added param' => 'random']
            ],
            'added param' => 'random'
        ]],
        'WithDefaultValues' => [new WithDefaultValues('value'), ['_type' => WithDefaultValues::class, 'a' => 'value', 'c' => 'random']]
    ]);

    it('is fast', function (int $n) {
        // Arrange
        $n = (int)(log(2 * $n + 1, 3) - 1);
        $withArray = new ClassFactory()->make($n, 3);
        $array = $withArray->toArray();

        // Act
        $before = microtime(true);
        Serializable::from($array);
        $ellapsed = microtime(true) - $before;

        // Assert
        expect($ellapsed)->toBeLessThan(0.5);
    })->with([
        [10],
        [100],
        [1000],
        [10000],
        [50000],
        [100000],
    ]);

    it('converts from string', function (Serializable $input) {
        // Arrange
        $json = json_encode($input->toArray());

        // Act
        $result = Serializable::from($json);

        // Assert
        expect($result)->toEqual($input);
    })->with([
        'EmptyClass' => [new EmptyClass()],
        'WithOneText' => [new WithOneText('the text')],
        'WithSubClass' => [new WithSubClass(new WithOneText('the text'))],
        'WithArray' => [new WithArray([new EmptyClass, new WithOneText('the text')])],
        'WithDefaultValues' => [new WithDefaultValues('a')]
    ]);

    it('converts from another object of same or other type', function (object $input, Serializable $expected) {
        // Arrange
        $class = get_class($expected);

        // Act
        $result = $class::from($input);

        // Assert
        expect($result)->toBeInstanceOf($class);
        expect($result->toArray())->toBe($expected->toArray());
    })->with([
        'EmptyClass' => [new WithOneText('the text'), new EmptyClass()],
        'WithOneText' =>  [(object)['text' => 'the text'], new WithOneText('the text')],
        'WithSubClass' => [(object)['subClass' => ['_type' => WithOneText::class, 'text' => 'the text']], new WithSubClass(new WithOneText('the text'))],
        'WithArray' => [
            new WithArray([
                new WithOneText('the text'),
                new WithTwoTexts('the text 2', 'random'),
            ]),
            new WithArrayAndAttribute([new WithOneText('the text'), new WithOneText('the text 2')])
        ],
        'WithArray 2' => [
            new WithArrayAndAttribute([
                ['_type' => WithOneText::class, 'text' => 'the text', 'added param' => 'random'],
                new WithTwoTexts('the text 2', 'random')
            ]),
            new WithArrayAndAttribute([new WithOneText('the text'), new WithOneText('the text 2')])
        ],
        'ChildOfWithOneText' => [
            new WithArray([new ChildOfWithOneText('the text')]),
            new WithArrayAndAttribute([new ChildOfWithOneText('the text')])
        ]
    ]);

    it('throws if Union type', function () {
        WithUnionTypeParam::from([]);
    })->throws(UnionTypeCannotBeUnserializedException::class);

    it('throws if Intersection type', function () {
        WithIntersectionTypeParam::from([]);
    })->throws(IntersectionTypeCannotBeUnserializedException::class);

    it('throws if no ArrayType', function () {
        WithNoArrayType::from(['array' => []]);
    })->throws(ArrayTypeIsMissingException::class);

    it('throws if types do not match type', function (string $class, array $input) {
        $class::from($input);
    })->throws(TypesDoNotMatchException::class)->with([
        [WithOneText::class, ['text' => 4]],
        [WithOneText::class, ['text' => new stdClass]],
        [WithArray::class, ['array' => new stdClass]],
        [WithArrayOfStrings::class, ['array' => [4]]]
    ]);

    it('throws if property does not exist', function () {
        // Arrange
        $array = [];

        // Act
        WithOneText::from($array);
    })->throws(MissingPropertyException::class);
});

describe('#fromRequest', function () {
    it('converts from Request', function (Serializable $expected) {
        // Arrange
        $array = $expected->toArray();
        $request = Request::create('/route', 'POST', $array);

        // Act
        $result = get_class($expected)::fromRequest($request);

        // Assert
        expect($result)->toBeInstanceOf(get_class($expected));
        expect($result->toArray())->toBe($expected->toArray());
    })->with([
        'EmptyClass' => [new EmptyClass()],
        'WithOneText' => [new WithOneText('the text')],
        'WithSubClass' => [new WithSubClass(new WithOneText('the text'))],
        'WithArray' => [new WithArray([new EmptyClass, new WithOneText('the text')])],
        'WithDefaultValues' => [new WithDefaultValues('a')],
        'WithNoTypeParam' => [new WithNoTypeParam('the text'),],
    ]);

    it('validates', function () {
        // Arrange
        $request = Request::create('/route', 'POST', []);

        // Act
        WithOneText::fromRequest($request);
    })->throws(ValidationException::class);
});

describe('#validate', function () {
    it('validates based on types and constructor definition (does not throw)', function (array $input, string $class) {
        // Act
        $class::validate($input);
    })->throwsNoExceptions()->with([
        [[], EmptyClass::class],
        'optional' => [[], WithOptionalValue::class],
        'default value' => [['a' => 'value'], WithDefaultValues::class],
        'empty array' => [['array' => []], WithArray::class],
        'array' => [['array' => [['_type' => WithOneText::class, 'text' => 'the text']]], WithArrayAndAttribute::class],
        'more element than necessary' => [['_type' => WithOneText::class, 'text' => 'the text', 'other' => 'value'], WithOneText::class],
        'with combination of attribute rules' => [['text' => 'f@a.c'], WithCombinationOfRules::class],
        'WithArrayOfStrings' => [['array' => ['a', 'b']], WithArrayOfStrings::class],
        'WithArrayOfArrays' => [['array' => [[], ['b' => 'c']]], WithArrayOfArrays::class],
        'WithArrayOfMixed' => [['array' => ['a', ['b' => 'c']]], WithArrayOfMixed::class],
        'WithSubClass' => [new WithSubClass(new WithOneText('the text'))->toArray(), WithSubClass::class],
    ]);

    it('fails if _types do not fit', function (string $type, string $class) {
        // Act
        $class::validate(['_type' => $type]);
    })->throws(ValidationException::class)->with([
        [EmptyClass::class, ChildOfEmptyClass::class],
        [WithOneText::class, EmptyClass::class]
    ]);

    it('validates based on types and constructor definition (throws)', function (array $input, string $class, array $errorKeys) {
        // Act
        try {
            $class::validate($input);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            expect($errors)->toHaveKeys($errorKeys);
            throw $e;
        }
    })->throws(ValidationException::class)
        ->with([
            'string' => [['text' => 45], WithOneText::class, ['text']],
            'number' => [['number' => 'text'], WithOneInt::class, ['number']],
            'required' => [[], WithOneText::class, ['text']],
            'array' => [[], WithArray::class, ['array']],
            'array: missing property in array element' => [['array' => [[]]], WithArrayAndAttribute::class, ['array.0.text']],
            'array element does not fit' => [['array' => [['_type' => WithTwoTexts::class]]], WithArrayAndAttribute::class, ['array.0._type']],
            'with attribute rule' => [['text' => 'a'], WithAttributeRule::class, ['text']],
            'with combination of attribute rules' => [['text' => 'a'], WithCombinationOfRules::class, ['text']],
            'with combination of attribute rules' => [['text' => 'aaaaaaaaaaaaaaaaaaaaaaa'], WithCombinationOfRules::class, ['text']],
            'with combination of attribute rules' => [['text' => 'abcd'], WithCombinationOfRules::class, ['text']],
            'with combination of attribute rules' => [['text' => 'a@b.cd'], WithCombinationOfRules::class, ['text']],
            'WithArrayOfStrings' => [['array' => ['a', 4]], WithArrayOfStrings::class, ['array.1']],
            'WithArrayOfArrays' => [['array' => ['a', ['b' => 'c']]], WithArrayOfArrays::class, ['array.0']],
            'WithSubClass' => [['subClass' => ['text' => 4]], WithSubClass::class, ['subClass.text']],
        ]);
});

describe('#toResponse', function () {
    it('creates a response', function (Request $request, Response $expected) {
        // Arrange
        $data = new WithOneText('the text');

        // Act
        $response = $data->toResponse($request);

        // Assert
        expect($response->getContent())->toEqual($expected->getContent());
        expect($response->getStatusCode())->toEqual($expected->getStatusCode());
    })->with([
        [fn() => Request::create('/url', 'POST'), new JsonResponse(data: new WithOneText('the text')->toArray(), status: Response::HTTP_CREATED)],
        [fn() => Request::create('/url', 'GET'), new JsonResponse(data: new WithOneText('the text')->toArray(), status: Response::HTTP_OK)],
    ]);
});

describe('#collect', function () {
    it('creates an array of the type from the input', function () {
        // Arrange
        $oneTexts = [new WithOneText('a')->toArray(), ['text' => 'b'], new WithOneText('c')];

        // Act
        $result = WithOneText::collect($oneTexts);

        // Assert
        $expected = array_map(fn($letter) => new WithOneText($letter)->toArray(), ['a', 'b', 'c']);
        expect($result)->each->toBeInstanceOf(WithOneText::class);
        expect(array_map(fn($r) => $r->toArray(), $result))->toBe($expected);
    });
});

todo('arrayrule for scalar elements ?');
