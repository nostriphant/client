<?php

namespace nostriphant\Client;

readonly class Client {
    
    private array $connections;
    
    public function __construct(\Amp\Websocket\Client\WebsocketConnection ...$connections) {
        $this->connections = $connections;
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
    
    public function __invoke(callable $speak_callback): callable {
        $subscriptions = [];
        $events = [];
        
        $speakers = array_map(fn($connection) => new Speech($connection), $this->connections);
        $listeners = array_map(fn($connection) => new Hearing($connection), $this->connections);
        
        $speak = fn(\nostriphant\NIP01\Message $message) => array_walk($speakers, fn(Speech $speak) => $speak($message));
        
        
        $event = function(\nostriphant\NIP01\Event $event, callable $reply) use ($speak, &$events) {
            $events[$event->id] = $reply;
            return $speak(\nostriphant\NIP01\Message::event($event));
        };
        
        $subscribe = function(array $filters, callable $reply) use ($speak, &$subscriptions) {
            $subscription_id = bin2hex(random_bytes(32));
            $subscriptions[$subscription_id] = fn(\nostriphant\NIP01\Event $event) => $reply($event, fn() => $speak(\nostriphant\NIP01\Message::close($subscription_id)));
            
            error_log('Subscribing with filters' . json_encode($filters), E_USER_NOTICE);
            return $speak(\nostriphant\NIP01\Message::req($subscription_id, $filters));
        };
        
        $receive_message = function(\nostriphant\NIP01\Message $message) use (&$subscriptions, &$events) {
            return match($message->type) {
                'EVENT' => $subscriptions[$message->payload[0]](new \nostriphant\NIP01\Event(...$message->payload[1])),
                'OK' => $events[$message->payload[0]]($message->payload[1], $message->payload[2]),
                'EOSE' => error_log('No more events in ' . $message->payload[0]),
                default => error_log($message)
            };
        };
        
        $futureSpeaking = \Amp\async(fn() => $speak_callback($event, $subscribe));
        $futureListening = \Amp\async(fn() => array_walk($listeners, fn(Hearing $listener) => $listener($receive_message)));
        \Amp\Future\await([$futureSpeaking, $futureListening], new \Amp\SignalCancellation([SIGINT, SIGTERM]));
        $this->connection->close();
    }
}
