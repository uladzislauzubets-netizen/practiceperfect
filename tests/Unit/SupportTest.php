<?php

declare(strict_types=1);

use App\Rules\ChildAge;
use App\Support\AvailabilitySummary;

it('formats time ranges compactly', function (string $start, string $end, string $expected) {
    expect(AvailabilitySummary::range($start, $end))->toBe($expected);
})->with([
    ['17:00:00', '20:00:00', '5-8pm'],
    ['09:00', '11:00', '9-11am'],
    ['11:00', '13:30', '11am-1:30pm'],
    ['00:00', '23:59', '12am-11:59pm'],
    ['17:30', '19:00', '5:30-7pm'],
]);

it('names days', function () {
    expect(AvailabilitySummary::dayName(1))->toBe('Mon')->and(AvailabilitySummary::dayName(7))->toBe('Sun');
});

it('validates child ages between 1 and 18', function (string $input, bool $valid) {
    $date = str_starts_with($input, '-') ? now()->modify($input)->toDateString() : $input;
    $failed = false;
    (new ChildAge)->validate('birth_date', $date, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBe(! $valid);
})->with([
    'infant under one' => ['-6 months', false],
    'one year' => ['-1 year', true],
    'ten' => ['-10 years', true],
    'eighteen' => ['-18 years', true],
    'nineteen' => ['-19 years', false],
    'garbage' => ['not a date', false],
]);
