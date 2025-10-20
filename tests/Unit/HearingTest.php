<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Hearing;

it('hearing works', function () {
    $connection = \Mockery::mock(\Amp\Websocket\Client\WebsocketConnection::class);
    $connection->shouldReceive('getIterator')->andReturn(new \ArrayIterator([\Amp\Websocket\WebsocketMessage::fromText('["EVENT", {}]')]));
    
    $listener = new Hearing($connection);
    $listener(function(\nostriphant\NIP01\Message $message) {
        expect($message->type)->toBe('EVENT');
    });
});
