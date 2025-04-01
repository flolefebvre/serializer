<?php

namespace Tests\Unit;

use Tests\Helper\Models\Single;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Helper\Classes\WithOneText;
use Tests\Helper\Models\ListModel;

describe('#get #set', function () {

    beforeEach(function () {
        // Arrange
        Schema::create('test_table', function (Blueprint $table) {
            $table->id();
            $table->jsonb('value');
        });
    });

    it('converts single value', function () {
        // Act
        Single::create([
            'value' => new WithOneText('the text')
        ]);

        // Assert
        $value = Single::first()->value;
        expect($value)->toBeInstanceOf(WithOneText::class);
        expect($value)->text->toBe('the text');
    });

    it('converts list', function () {
        // Act
        ListModel::create([
            'value' => [new WithOneText('the text')]
        ]);

        // Assert
        $value = ListModel::first()->value;
        expect($value)->toBeArray();
        expect($value)->toHaveCount(1);
        $firstValue = $value[0];
        expect($firstValue)->toBeInstanceOf(WithOneText::class);
        expect($firstValue)->text->toBe('the text');
    });
});
