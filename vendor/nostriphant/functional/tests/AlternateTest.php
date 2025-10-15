<?php

namespace nostriphant\FunctionalTests;

use nostriphant\Functional\Alternate;

Pest::extend('expect');

it('usage works', function () {
    $state = Alternate::state1('Hello World!');

    $evaluate = $state(state1: function (string $message) {
        yield $message;
    });

    foreach ($evaluate as $message) {
        expect($message)->toBe('Hello World!');
    }
});

it('can alternate execution paths', function () {
    $alternate = Alternate::first('Hello World');
    expect($alternate)->toHaveState(first: ['Hello World']);
});

it('ignores undefined execution paths', function () {
    $alternate = Alternate::second('Hello World');
    foreach ($alternate() as $msg) {

    }
})->throwsNoExceptions();

it('can fallback to a default execution paths', function () {
    $alternate = Alternate::first('Hello World');
    expect($alternate)->toHaveState(default: ['Hello World']);
});
