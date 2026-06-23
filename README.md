# Nostriphant Client

Simple nostr php client

```php
<?php

use nostriphant\Client\Client;

$client = Client::connectToUrl("wss://nos.lol", "wss://relay.damus.io");

$client(function(callable $send, callable $subscribe) {
    $subscription = $subscribe(ids: ['hex_event_id']);
    $subscription(function(?\nostriphant\NIP01\Event $event, callable $close, callable $stop) {
        if (is_null($event)) {
            // EOSE
        } else {
            // EVENT RECEIVED
        }
    });

    $event = new \nostriphant\NIP01\Event(...);
    $send($event, function(bool $accepted, string $reason, callable $stop) {
        // handle response
    });
});

```
