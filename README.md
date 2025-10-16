# Nostriphant Client

Simple nostr php client

```php
<?php

use nostriphant\NIP01\Message;
use nostriphant\Client\Client;

$client = new Client('wss://nostr.example.org', function (Message $message) {
    // code to handle incoming messages
});

$listen = $client(function(callable $send) {

    // connection has been established, start communicating here
   $send(Message::event(new \nostriphant\NIP59\Rumor(time(), 'pubkey', 1, 'Hello World!', [])));

});

$listen(fn(int $signal) =>  printf("Received signal %d, stopping client", $signal));

```
