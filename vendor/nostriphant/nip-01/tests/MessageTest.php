<?php

use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;
use nostriphant\NIP01\Key;
use nostriphant\NIP01\Nostr;

it('can construct a message', function () {
    $message = new Message('TYPE', 'a', ['b' => 'c']);
    expect($message())->toBe(['TYPE', 'a', ['b' => 'c']]);
});

it('can convert a message to string', function () {
    $message = new Message('TYPE', 'a');
    expect($message . '')->toBe('["TYPE","a"]');
});

it('can decode a string into a message', function () {
    $message = Message::decode('["TYPE", "a", {"b":"c"}]');
    expect($message())->toBe(['TYPE', 'a', ['b' => 'c']]);
});


it('can fabric a message', function () {
    $message = Message::event('a', ['b' => 'c']);
    expect($message())->toBe(['EVENT', 'a', ['b' => 'c']]);
});

it('can fabric a message with an Event', function () {
    $message = Message::event($event = new Event(
            id: '',
            pubkey: '',
            created_at: 1734349976,
            kind: 1,
            content: 'Hello World',
            sig: '',
            tags: []
    ));

    expect($message())->toBe(["EVENT", get_object_vars($event)]);
    expect('' . $message)->toBe('["EVENT",{"id":"","pubkey":"","created_at":1734349976,"kind":1,"content":"Hello World","sig":"","tags":[]}]');
});

it('can generate a properly signed note', function () {
    $private_key = Key::fromHex('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    $at = time();

    $event_id = hash('sha256', Nostr::encode([0, $private_key(Key::public()), $at, 1, [], 'Hello world!']));
    $signed_message = Message::event(new Event(
                    id: $event_id,
                    pubkey: $private_key(Key::public()),
                    created_at: $at,
                    kind: 1,
                    content: 'Hello world!',
                    tags: [],
                    sig: $private_key(Key::signer($event_id))
            ));

    expect($signed_message->type)->toBe('EVENT');
    expect($signed_message->payload)->toBeArray();
    $event_scaffolded = array_merge([
        "id" => null,
        "pubkey" => null,
        "created_at" => null,
        "kind" => null,
        "tags" => null,
        "content" => null,
        "sig" => null
            ], $signed_message->payload[0]);
    array_walk($event_scaffolded, fn(mixed $value, string $key) => expect($value)->not()->toBeNull($key . ' not set'));

    expect($signed_message->payload[0]['kind'])->toBe(1);
    expect($signed_message->payload[0]['content'])->toBe('Hello world!');
    expect($signed_message->payload[0]['tags'])->toBe([]);
    expect($signed_message->payload[0]['created_at'])->toBeInt();
    expect($signed_message->payload[0]['pubkey'])->toBe('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');

    expect(Key::verify($signed_message->payload[0]['pubkey'], $signed_message->payload[0]['sig'], $signed_message->payload[0]['id']))->toBeTrue();
});

it('can create a subscribe message with a kinds filter', function () {
    $subscription = Message::req(bin2hex(random_bytes(32)), ["kinds" => [1]]);
    expect($subscription)->toBeCallable();

    $message = $subscription();
    expect($message[0])->toBe('REQ');
    expect($message[1])->toBeString();
    expect(strlen($message[1]) <= 64)->toBeTrue();
    expect(str_contains($message[1], ' '))->toBeFalse();
    expect($message[2]['kinds'])->toBe([1]);
});
it('can create a subscribe message with multiple filters', function () {
    $subscription = Message::req(bin2hex(random_bytes(32)),
            ["kinds" => [1]],
            ["since" => 1724755392]
    );

    $message = $subscription();
    expect($message[0])->toBe('REQ');
    expect($message[1])->toBeString();
    expect(strlen($message[1]) <= 64)->toBeTrue();
    expect(str_contains($message[1], ' '))->toBeFalse();
    expect($message[2]['kinds'])->toBe([1]);
    expect($message[3]['since'])->toBe(1724755392);
});

it('can create a subscribe message with different filter-conditions', function () {
    $subscription = Message::req(bin2hex(random_bytes(32)), [
        "ids" => ["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"],
        "authors" => ["5ab2a1fc-40b2-4ae1-85a4-4d207330d3c1", "b618d576-bf3c-4f5a-9334-d9c860b142b4"],
        "kinds" => [1, 2, 4, 6],
        //"#<single-letter (a-zA-Z)>" => <a list of tag values, for #e — a list of event ids, for #p — a list of pubkeys, etc.>,
        "since" => 1724755392,
        "until" => 1756284192,
        "limit" => 25
    ]);

    $message = $subscription();
    expect($message[0])->toBe('REQ');
    expect($message[1])->toBeString();
    expect(strlen($message[1]) <= 64)->toBeTrue();
    expect(str_contains($message[1], ' '))->toBeFalse();
    expect($message[2]['ids'])->toBe(["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"]);
    expect($message[2]['authors'])->toBe(["5ab2a1fc-40b2-4ae1-85a4-4d207330d3c1", "b618d576-bf3c-4f5a-9334-d9c860b142b4"]);
    expect($message[2]['kinds'])->toBe([1, 2, 4, 6]);
    expect($message[2]['since'])->toBe(1724755392);
    expect($message[2]['until'])->toBe(1756284192);
    expect($message[2]['limit'])->toBe(25);
});

it('does not allow for unknown filters, merge tags', function () {

    $subscription = Message::req(bin2hex(random_bytes(32)),
            ["kinds" => [1]],
            ['#e' => ["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"]]
    );
    $message = $subscription();
    expect($message)->toHaveLength(4);
    expect($message[3]['#e'])->toBe(["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"]);
});
