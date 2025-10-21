# Nostriphant Client

Simple nostr php client

```php
<?php

use nostriphant\NIP01\Message;
use nostriphant\Client\Client;

$client = Client::connectToUrl("wss://nos.lol");

$listen = $client(function(\nostriphant\NIP01\Transmission $send) {
    // connection has been established, start communicating here
    $send(Message::event(new \nostriphant\NIP59\Rumor(time(), 'pubkey', 1, 'Hello World!', [])));
});

listen(function(\nostriphant\NIP01\Message $message, callable $stop) {
    // code to handle incoming messages

    $stop(); // stops listening
});

$listen(fn(int $signal) =>  printf("Received signal %d, stopping client", $signal));

```
