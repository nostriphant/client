<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Client;

it('can be instantiated', function () {
    $client = new Client("wss://nos.lol");
    $client(function (callable $send_event, callable $subscribe) {
        $key = \nostriphant\NIP01\Key::generate();
        $sent_event = new \nostriphant\NIP01\Rumor(time(), 1, 'Hello World!', [])($key);

        $subscription = $subscribe(ids: [$sent_event->id]);
        $send_event($sent_event, fn(bool $accepted) => $subscription(function (?\nostriphant\NIP01\Event $event, callable $close, callable $stop) use ($sent_event) {
                    if (is_null($event)) {
                        $stop();
                    } else {
                        expect($event->id)->toBe($sent_event->id);
                    }
                }));

    });
});

it('can connect to wss://nos.lol, submit an req and wait for events to come back', function() {
    $client = new Client("wss://nos.lol");

    $client(function(callable $send_event, callable $subscribe) {
        $subscription = $subscribe(kinds: [1], limit: 1);
        $subscription(function(\nostriphant\NIP01\Event $event, callable $close, callable $stop) {
            expect($event->kind)->toBe(1);
            $stop();
        });
    });
});