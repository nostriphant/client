<?php

namespace nostriphant\Client;

readonly class Client {
    
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {
    }
    
    public static function connectToUrl(string $url) {
        return new self(\Amp\Websocket\Client\connect($url, new \Amp\SignalCancellation([SIGINT, SIGTERM])));
    }
    
    public function __invoke(callable $speak_callback): callable {
        $speak_callback(new Speech($this->connection));
        $listener = new Hearing($this->connection);
        
        return function(callable $response_callback) use ($listener) : void {
            $future = \Amp\async(fn() => $listener($response_callback)); 
        
            $future->await(new \Amp\SignalCancellation([SIGINT, SIGTERM]));
            $this->connection->close();
        };
    }
}
