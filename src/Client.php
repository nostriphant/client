<?php

namespace nostriphant\Client;
use nostriphant\NIP01\Message;
readonly class Client {
    
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {
    }
    
    public static function connectToUrl(string $url) {
        return new self(\Amp\Websocket\Client\connect($url, new \Amp\SignalCancellation([SIGINT, SIGTERM])));
    }
    
    public function __invoke(callable $speak_callback): callable {
        $speak_callback(new Speech($this->connection));
        
        return function(callable $response_callback) : void {
            $listener = new Hearing($this->connection);
            $future = \Amp\async(fn() => $listener(fn(Message $message) => $response_callback($message, fn() => $this->connection->close()))); 
            $future->await(new \Amp\SignalCancellation([SIGINT, SIGTERM]));
        };
    }
}
