<?php

use nostriphant\NIP01\Nostr;

it('encodes and decodes data', function (mixed $in, string $expected) {
    $encoded = Nostr::encode($in);
    expect($encoded)->toBe($expected);
    expect(Nostr::decode($encoded))->toBe($in);
})->with([
    [['this' => 'is data'], json_encode(['this' => 'is data'])]
]);


it('fails when invalid json', function () {
    expect(fn() => Nostr::decode('{invalid:json}'))->toThrow(\InvalidArgumentException::class);
});
