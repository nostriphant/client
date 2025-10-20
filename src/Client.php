<?php

namespace nostriphant\Client;

use nostriphant\Functional\Await;

readonly class Client {
    
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {
    }
    
    public static function connectToUrl(string $url) {
        return new self(\Amp\Websocket\Client\connect($url, new \Amp\NullCancellation()));
    }
    
    public function __invoke(callable $bootstrap_callback, callable $response_callback): callable {
        $listener = new Hearing($this->connection);
        \Amp\async(fn() => $listener($response_callback));
        
        $bootstrap_callback(new Speech($this->connection));
        
        return fn(callable $shutdown_callback) => (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM], false)))(function(int $signal) use ($shutdown_callback) {
            $shutdown_callback($signal);
            $this->connection->close();
        });
    }
}
