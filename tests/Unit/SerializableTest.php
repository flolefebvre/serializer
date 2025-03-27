<?php

use Flolefebvre\Serializer\Serializable;
use Tests\Helper\Classes\EmptyClass;
use Tests\Helper\Classes\WithArray;
use Tests\Helper\Classes\WithOneText;
use Tests\Helper\Classes\WithSubClass;
use Tests\Helper\ClassFactory;

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
    ]);

    // it('unserializes from array with additionnal data', function (Serializable $input, array $array) {
    //     // Arrange
    //     $array = $input->toArray();

    //     // Act
    //     $result = Serializable::from($array);

    //     // Assert
    //     expect($result)->toEqual($input);
    // })->with([
    //     'EmptyClass' => [new EmptyClass(), ['_type' => EmptyClass::class]],
    //     'WithOneText' => [new WithOneText('the text'), ['_type' => WithOneText::class, 'text' => 'the text']],
    //     'WithSubClass' => [new WithSubClass(new WithOneText('the text')), [
    //         '_type' => WithSubClass::class,
    //         'subClass' => ['_type' => WithOneText::class, 'text' => 'the text']
    //     ]],
    //     'WithArray' => [new WithArray([new EmptyClass, new WithOneText('the text')]), [
    //         '_type' => WithArray::class,
    //         'array' => [
    //             ['_type' => EmptyClass::class],
    //             ['_type' => WithOneText::class, 'text' => 'the text']
    //         ]
    //     ]],
    // ]);

    it('is fast', function (int $n) {
        // Arrange
        $n = (int)(log(2 * $n + 1, 3) - 1);
        $withArray = new ClassFactory()->make($n, 3);
        $array = $withArray->toArray();

        // Act
        $before = microtime(true);
        Serializable::from($array);
        $ellapsed = microtime(true) - $before;
        var_dump($ellapsed);

        // Assert
        expect($ellapsed)->toBeLessThan(1);
    })->with([
        [100],
        [1000],
        [10000],
        [100000],
    ]);
});
