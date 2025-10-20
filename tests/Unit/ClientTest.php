<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Client;

it('client can be instantiated', function () {
    $connection = \Mockery::mock(\Amp\Websocket\Client\WebsocketConnection::class);
    $connection->shouldReceive('getIterator')->andReturn(new \ArrayIterator([\Amp\Websocket\WebsocketMessage::fromText('["EVENT", {}]')]));
    $connection->shouldReceive('close');
    
    $agent = new Client($connection);
    $wait = $agent(fn() => null, function(\nostriphant\NIP01\Message $message) use (&$agent) {
        var_dump($message);
    });
    
});
