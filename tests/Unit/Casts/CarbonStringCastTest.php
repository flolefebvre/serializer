<?php

namespace Tests\Unit\Casts;

use Carbon\Carbon;
use Flolefebvre\Serializer\Casts\CarbonStringCast;
use InvalidArgumentException;

describe('#toArray', function () {
    it('throws if not carbon date', function () {
        // Act
        new CarbonStringCast()->serialize('string');
    })->throws(InvalidArgumentException::class);

    it('serializes', function () {
        // Arrange
        $date = Carbon::create(2025, 4, 1, 10, 0, 0, 'GMT');

        // Act
        $result = new CarbonStringCast()->serialize($date);

        // Assert
        expect($result)->toBe('2025-04-01T10:00:00+00:00');
    });
});

describe('#unserialize', function () {
    it('throws if not string', function () {
        // Act
        new CarbonStringCast()->unserialize(5);
    })->throws(InvalidArgumentException::class);

    it('unserializes', function () {
        // Act
        $result = new CarbonStringCast()->unserialize('2025-04-01T10:00:00+00:00');

        // Assert
        expect($result)->toEqual(Carbon::create(2025, 4, 1, 10, 0, 0, 'GMT'));
    });

    it('unserializes from Carbon date', function () {
        // Arrange
        $date = Carbon::create(2025, 4, 1, 10, 0, 0, 'GMT');

        // Act
        $result = new CarbonStringCast()->unserialize($date);

        // Assert
        expect($result)->not->toBe($date);
        expect($result)->toEqual(Carbon::create(2025, 4, 1, 10, 0, 0, 'GMT'));
    });
});
