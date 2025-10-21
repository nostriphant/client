<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Client;

it('client can be instantiated', function () {
    $connection = \Mockery::mock(\Amp\Websocket\Client\WebsocketConnection::class);
    $connection->shouldReceive('getIterator')->andReturn(new \ArrayIterator([\Amp\Websocket\WebsocketMessage::fromText('["EVENT", {}]')]));
    $connection->shouldReceive('close');
    
    
    $client = new Client($connection);
    $listen = $client(fn() => null);
    
    
    $message_received = false;
    $listen(function(\nostriphant\NIP01\Message $message) use ($message_received) {
        $message_received = true;
        expect($message->type)->toBe('EVENT');
    });
    
});

it('can connect to wss://nos.lol, submit an req and wait for events to come back', function() {
    $client = Client::connectToUrl("wss://nos.lol");
    
    $listen = $client(function(\nostriphant\NIP01\Transmission $send) {
        $send(\nostriphant\NIP01\Message::req("my-subscription", ["kinds"=> [1], "limit"=>1]));
    });
    $listen(function(\nostriphant\NIP01\Message $message, callable $stop) {
        switch ($message->type) {
            case 'EVENT':
                expect($message->payload[0])->toBe("my-subscription");
                expect($message->payload[1]['kind'])->toBe(1);
                break;
            
            case 'EOSE':
                expect($message->payload[0])->toBe("my-subscription");
                $stop();
                break;
        }
    });
});