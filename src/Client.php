<?php

namespace nostriphant\Client;

readonly class Client {
    
    public function __construct(private \Amp\Websocket\Client\WebsocketConnection $connection) {
    }
    
    public static function connectToUrl(string $url) {
        return new self(\Amp\Websocket\Client\connect($url, new \Amp\SignalCancellation([SIGINT, SIGTERM])));
    }
    
    public function __invoke(callable $speak_callback): callable {
        $subscriptions = [];
        
        $speak = new Speech($this->connection);
        $speak_callback($speak, function(array $filters, callable $reply) use ($speak, &$subscriptions) {
            $subscription_id = bin2hex(random_bytes(4));
            $subscriptions[$subscription_id] = $reply;
            $speak(\nostriphant\NIP01\Message::req($subscription_id, ...$filters));
        });
        
        $listener = new Hearing($this->connection);
        $future = \Amp\async(fn() => $listener(fn(\nostriphant\NIP01\Message $message) => match($message->type) {
            'EVENT' => $subscriptions[$message->payload[0]](new \nostriphant\NIP01\Event(...$message->payload[1])),
            default => error_log($message)
        }));
        
        $future->await(new \Amp\SignalCancellation([SIGINT, SIGTERM]));
        $this->connection->close();
    }
}
