<?php

namespace nostriphant\Client;

use nostriphant\NIP01\Message;

readonly class Hearing {
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {
        
    }
    
    public function __invoke(callable $response_callback) : void {
        $listening = true;
        foreach ($this->connection as $message) {
            $response_callback(Message::decode($message->read()), function() use (&$listening) {
                $listening = false;
            });
            
            if ($listening === false) {
                break;
            }
        }
    }
}
