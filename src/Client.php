<?php

namespace nostriphant\Client;

readonly class Client {
    
    private array $connections;
    
    private array $speakers;
    private array $listeners;
    
    public function __construct(\Amp\Websocket\Client\WebsocketConnection ...$connections) {
        $this->connections = $connections;
        
        $this->speakers = array_map(fn($connection) => new Speech($connection), $this->connections);
        $this->listeners = array_map(fn($connection) => new Hearing($connection), $this->connections);
    }
    
    public static function connectToUrl(string ...$urls) {
        $connections = [];
        $attempts = 0;
        foreach ($urls as $url) {
            try {
                retry:
                $connections[] = \Amp\Websocket\Client\connect($url, new \Amp\SignalCancellation([SIGINT, SIGTERM]));
            } catch (\Amp\Websocket\Client\WebsocketConnectException $e) {
                error_log('Failed connecting, retry in 5 seconds');
                sleep(5);
                $attempts++;
                if ($attempts<5) {
                    goto retry;
                }
            } catch (\Amp\Http\Client\SocketException $e) {
                error_log('Failed connecting to ' . $url.': ' . $e->getMessage());
                continue;
            } catch (\Amp\TimeoutException $e) {
                error_log('Failed connecting to ' . $url.': timeout');
                continue;
            }
        }

        return new self(...$connections);
    }
    
    public function __invoke(callable $speak_callback) : void {
        $subscriptions = new \nostriphant\Functional\Index();
        $events = new \nostriphant\Functional\Index();
        
        $speak = function(\nostriphant\NIP01\Message $message) {
            return \nostriphant\Functional\Iterator::walk($this->speakers, fn(Speech $speak) => \Amp\async(fn() => $speak($message)));
        };
        
        
        $event = function(\nostriphant\NIP01\Event $event, callable $reply) use ($speak, $events) {
            $events[$event->id] = $reply;
            return $speak(\nostriphant\NIP01\Message::event($event));
        };
        
        
        $futureSpeaking = \Amp\async($speak_callback, $event, new SubscriptionFactory($speak, $subscriptions));
        $futureListening = \Amp\async(fn() => \nostriphant\Functional\Iterator::walk($this->listeners, fn(Hearing $listener) => $listener(fn(\nostriphant\NIP01\Message $message, callable $stop) => match($message->type) {
            'EVENT' => $subscriptions[$message->payload[0]](new \nostriphant\NIP01\Event(...$message->payload[1]), $stop),
            'OK' => $events[$message->payload[0]]($message->payload[1], $message->payload[2], $stop),
            'EOSE' => error_log('No more events in ' . $message->payload[0]),
            default => error_log($message)
        })));
        
        \Amp\Future\await([$futureSpeaking, $futureListening], new \Amp\SignalCancellation([SIGINT, SIGTERM]));
        
        \nostriphant\Functional\Iterator::walk($this->connections, fn(\Amp\Websocket\Client\WebsocketConnection $connection, int $index) => $connection->close());
    }
}
