<?php
namespace nostriphant\ClientTests;

use nostriphant\Client\Client;

it('client can be instantiated', function () {
    $agent = Client::connectToUrl('wss://127.0.0.1');
    expect($agent)->toBeInstanceOf(Client::class);
    //, function (Message $message) {}
});
