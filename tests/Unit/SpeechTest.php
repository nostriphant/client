<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Speech;

it('speech works', function () {
    $connection = \Mockery::mock(\Amp\Websocket\Client\WebsocketConnection::class);
    $connection->expects('sendText')->with('["EVENT",{"foo":"bar"}]');
    
    $speaker = new Speech($connection);
    $speaker(\nostriphant\NIP01\Message::decode('["EVENT", {"foo": "bar"}]'));
});
