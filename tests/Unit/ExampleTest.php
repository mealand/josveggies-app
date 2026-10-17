<?php

use App\Support\Money;
use App\Support\Phone;

test('money is formatted from kobo without rounding errors', function () {
    expect(Money::format(1650000))->toBe('₦16,500')
        ->and(Money::format(1650050))->toBe('₦16,500.50')
        ->and(Money::toKobo('16,500.50'))->toBe(1650050)
        ->and(Money::toNaira(250000))->toBe('2500');
});

test('nigerian phone numbers are normalised', function (string $input, ?string $expected) {
    expect(Phone::normalize($input))->toBe($expected);
})->with([
    ['0803 123 4567', '2348031234567'],
    ['+234 803 123 4567', '2348031234567'],
    ['8031234567', '2348031234567'],
    ['12345', null],
]);
