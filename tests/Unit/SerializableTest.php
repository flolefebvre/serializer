<?php

use Tests\Helper\ClassFactory;
use Tests\Helper\Classes\WithArray;
use Tests\Helper\Classes\EmptyClass;
use Tests\Helper\Classes\WithOneText;
use Tests\Helper\Classes\WithSubClass;
use Tests\Helper\Classes\WithTwoTexts;
use Flolefebvre\Serializer\Serializable;
use Tests\Helper\Classes\WithNoArrayType;
use Tests\Helper\Classes\WithNoTypeParam;
use Tests\Helper\Classes\WithDefaultValues;
use Tests\Helper\Classes\ChildOfWithOneText;
use Tests\Helper\Classes\WithUnionTypeParam;
use Tests\Helper\Classes\WithArrayAndAttribute;
use Tests\Helper\Classes\WithIntersectionTypeParam;
use Flolefebvre\Serializer\Exceptions\MissingPropertyException;
use Flolefebvre\Serializer\Exceptions\TypesDoNotMatchException;
use Flolefebvre\Serializer\Exceptions\ArrayTypeIsMissingException;
use Flolefebvre\Serializer\Exceptions\UnionTypeCannotBeUnserializedException;
use Flolefebvre\Serializer\Exceptions\IntersectionTypeCannotBeUnserializedException;
use Illuminate\Http\Request;

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
        ]]
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

    it('throws if typses do not match type', function (string $class, array $input) {
        $class::from($input);
    })->throws(TypesDoNotMatchException::class)->with([
        [WithOneText::class, ['text' => 4]],
        [WithOneText::class, ['text' => new stdClass]],
        [WithArray::class, ['array' => new stdClass]],
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
});

todo('responsable ?');
todo('validate');
