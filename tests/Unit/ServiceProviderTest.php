<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Helper\Classes\WithOneText;
use function Pest\Laravel\post;

describe('#register', function () {
    it('is registered and calls the from method when resolving', function () {
        // Arrange
        app()->bind(Request::class, fn() =>  Request::create(
            '/dummy',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['text' => 'the text'])
        ));

        // Act
        $data = app(WithOneText::class);

        // Assert
        expect($data)->toEqual(new WithOneText('the text'));
    });

    it('works with controllers', function () {
        // Arrange
        Route::post('/myroute', function (WithOneText $withOneText) {
            return $withOneText->text;
        });

        // Act
        $res = post('/myroute', ['text' => 'mytext']);

        // Assert
        $res->assertOk();
        $res->assertContent('mytext');
    });
});
