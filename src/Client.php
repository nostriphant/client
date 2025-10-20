<?php

namespace nostriphant\Client;

use nostriphant\Transpher\Nostr\Transmission;
use nostriphant\NIP01\Message;
use nostriphant\Functional\Await;

readonly class Client {
    
    private function __construct(private string $relay_url) {
    }
    
    public static function connectToUrl(string $url) {
        return new self($url);
    }
    
    public function __invoke(callable $bootstrap_callback, callable $response_callback): callable {
        $connection = \Amp\Websocket\Client\connect($this->relay_url, new \Amp\NullCancellation());
        
        \Amp\async(function() use ($connection, $response_callback) {
            foreach ($connection as $message) {
                $response_callback(Message::decode($message->buffer()));
            }
        });
        $bootstrap_callback(new class($connection) implements Transmission {
            public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {

            }

            #[\Override]
            public function __invoke(Message $message): bool {
                $this->connection->sendText($message);
                return true;
            }

        });
        return fn(callable $shutdown_callback) => (new Await(fn() => \Amp\trapSignal([SIGINT, SIGTERM])))(function(int $signal) use ($shutdown_callback, $connection) {
            $shutdown_callback($signal);
            $connection->close();
        });
    }
}
