<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Client;

it('client can be instantiated', function () {
    $queue = [];
    
    $connection = \Mockery::mock(\Amp\Websocket\Client\WebsocketConnection::class);
    $connection->shouldReceive('getIterator')->andReturnUsing(function() use (&$queue) {
        $rounds = 0;
        while ($rounds<2) {
            while (count($queue) > 0) {
                yield \Amp\Websocket\WebsocketMessage::fromText(array_pop($queue));
            }
            \Amp\delay(1);
            $rounds++;
        }
    });
    $connection->shouldReceive('sendText')->withArgs(fn($arg) => 1 === preg_match('/'. preg_quote('["REQ","') . '\w{64}' . preg_quote('",{"authors":[]}]') . '/', $arg));
    
    $connection->shouldReceive('close');
    
    
    $client = new Client($connection);
    $message_received = false;
    $client(function(callable $send_event, callable $subscribe) use (&$message_received, &$queue, $connection, &$serving) {
        $subscription = $subscribe(authors: []);
        $subscription(function(\nostriphant\NIP01\Event $event, callable $close, callable $stop) use (&$message_received, &$serving) {
            $message_received = $event->id === '75370ec91ddb3e5a98fb943a458247b060d25123d1a5bc8dcdee24205bad681e';
            $stop();
        });
        $queue[] = '["EVENT", "'. new \ReflectionObject($subscription)->getProperty('id')->getValue($subscription) . '", {"id":"75370ec91ddb3e5a98fb943a458247b060d25123d1a5bc8dcdee24205bad681e","pubkey":"a09dd48846e55a697591d3563fdcbc1e074dc6dedf05160ba1a4e19a72ecaebb","created_at":1780392582,"kind":24133,"content":"Ap+OV3EETcyyJhkXwq8k8zVWTSDgQKpLw85+cFrqc9rdshs8TcFMYQAJ3RW5USUPRG88Ghj1N9b2JRqxMhkoHWQIc7Pb9nRn+qBd+dHdjmfnMhMeW5PsWs/cDNm0DLdCbn0tYfaC4CyE9MiVdzmVyQFg8lH7hdBY7FvHQX+NIiP2ZpySKnMEIXrXVrdknQ5ukXTYVtRThdrTkD5gT/IT85T4oSPj0CeRT4Oa/bi9yxCJ6aZhAzNqKyj1fY6W2gIHV/foHfXxyBuJ6xcQExsGpaTWDOFGAivzAYh6CLdxFyw5h18Uae8KM1eLhHZoDfiG9sY+O7JlRpOVhcGxGsoW5M8EWazNjgY4PqVFo/lT6P9PV9dyuglTihd1pFKnN8KX4laRNzF64U47vnNxJMWy4K1pIXS1BIOYs0WtukUHl2PcDxFbUA0+iKIamKvL/gJgeFk4u/lfLqgP5AMhzlU4pYCe4lMfBvr0oDhbv6xd7H36tQiKvt7iFN933xrPJHIrfWBIDRMsfXOkX+rfy1e5wJyYSblAfWWw2zpP1WVuxhHbYsbuuKX17B7uci5VS5JP+RwlqSB2WZeNTpODP2l8MkRVKfyKLZPTY/MkBBfHgvU/fneom8DgUBBcV6lrnfq5+QrgTiidUhkSwPhKN68OzksP76cA1sX3tm+9hoO5cyRDO02jgblOqEBQALM9pLR1oVYtX0+9gDkQ4Nj3BQTLsAtdIaKSurXKKlDdWOzvqB45qXNMgxVmDt7jWUjcUIjPRYumk1DQyZvoG+7RQAu1s0eVctlOFsMLswW6xfQ7xvkRA9YCNxCspNpeoTGYNi7+GKcDBoxtZHSLN60zZWofxl55pNy4bsDKmJO2zCZaToSNPfgcm6U4O9e5vhXU9OZu5s1D7LcHIIhHe8KtyQCvy7Gxlqve8S8i/ljF1/EjArSGh27LPGC1qR2f8QWU3X7AhBg1VE02vdw4+a+xngikhnYutDPGHZaQb6WqhJIKmoBsgizkWhcX/CsIKvqTp6xBWsLm98oMjCzIhpNsWiPMpeyDswjzQpr1U1yKU/iYUDszsQf2Hj//JmROSxaK23C0HwMR9tyth9v5I9Lo5nuPj6Wy7g==","sig":"bc3178c3973a37dc5a713b580af34d16af52ea3f21266012120faa8aa82cbf500d4687cb61e23b2c851f07c9a0d70d1ad0109b2bff20aaa5a7a104fb69d3cf00","tags":[["p","892c18b4e5866b277e4b9d6ba3656d45122d6e178c8834d275943195b21baaab"]]}]';
        
        
    });

    expect($message_received)->toBeTrue();
});

it('can connect to wss://nos.lol, submit an req and wait for events to come back', function() {
    $client = Client::connectToUrl("wss://nos.lol");
    
    $client(function(callable $send_event, callable $subscribe) {
        $subscription = $subscribe(kinds: [1], limit: 1);
        $subscription(function(\nostriphant\NIP01\Event $event, callable $close, callable $stop) {
            expect($event->kind)->toBe(1);
            $stop();
        });
    });
});