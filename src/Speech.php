<?php
namespace nostriphant\Client;

use nostriphant\NIP01\Transmission;
use nostriphant\NIP01\Message;

class Speech implements Transmission {
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {

    }

    #[\Override]
    public function __invoke(Message $message): bool {
        $this->connection->sendText($message);
        return true;
    }

}