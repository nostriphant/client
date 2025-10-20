<?php

namespace nostriphant\Client;

use nostriphant\NIP01\Message;

readonly class Hearing {
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {
        
    }
    
    public function __invoke(callable $response_callback) : void {
        foreach ($this->connection as $message) {
            $response_callback(Message::decode($message->read()));
        }
    }
}
