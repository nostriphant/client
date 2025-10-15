<?php

namespace nostriphant\FunctionalTests;

use nostriphant\Functional\Await;

it('await returns after completion', function () {
    $await = new Await(fn() => true);
    expect($await(fn(bool $result) => !$result))->toBeFalse();
});
