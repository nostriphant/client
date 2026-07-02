<?php

namespace nostriphant\Client;

readonly class Client {

    private array $urls;

    public function __construct(string $url, string ...$urls) {
        array_unshift($urls, $url);
        $this->urls = $urls;
    }

    public function __invoke(callable $speak_callback, ?callable $log = null): void {
        $log ??= fn() => null;

        $connections = [];
        $attempts = 0;
        foreach ($this->urls as $url) {
            try {
                retry:
                $connections[] = \Amp\Websocket\Client\connect($url, new \Amp\SignalCancellation([SIGINT, SIGTERM]));
            } catch (\Amp\Websocket\Client\WebsocketConnectException $e) {
                $log('Failed connecting, retry in 5 seconds');
                sleep(5);
                $attempts++;
                if ($attempts < 5) {
                    goto retry;
                }
            } catch (\Amp\Http\Client\SocketException $e) {
                $log('Failed connecting to ' . $url . ': ' . $e->getMessage());
                continue;
            } catch (\Amp\Http\Client\TimeoutException $e) {
                $log('Failed connecting to ' . $url . ': timeout');
                continue;
            }
            $log('Connected to ' . $url);
        }

        $speakers = array_map(fn($connection) => new Speech($connection), $connections);
        $listeners = array_map(fn($connection) => new Hearing($connection), $connections);

        $subscriptions = new \nostriphant\Functional\Index();
        $events = new \nostriphant\Functional\Index();

        $speak = function (\nostriphant\NIP01\Message $message) use ($speakers) {
            return \nostriphant\Functional\Iterator::walk($speakers, fn(Speech $speak) => \Amp\async(fn() => $speak($message)));
        };


        $event = function(\nostriphant\NIP01\Event $event, callable $reply) use ($speak, $events) {
            $events[$event->id] = $reply;
            return $speak(\nostriphant\NIP01\Message::event($event));
        };


        $speak_callback($event, new SubscriptionFactory($speak, $subscriptions));
        $futureListening = \Amp\async(\Closure::fromCallable([\nostriphant\Functional\Iterator::class, 'walk']), $listeners, fn(Hearing $listener) => $listener(fn(\nostriphant\NIP01\Message $message, callable $stop) => match ($message->type) {
                            'EVENT' => $subscriptions[$message->payload[0]](new \nostriphant\NIP01\Event(...$message->payload[1]), $stop),
                            'OK' => $events[$message->payload[0]]($message->payload[1], $message->payload[2], $stop),
                            'EOSE' => $subscriptions[$message->payload[0]](null, $stop),
                            default => $log($message)
                        }));

        \Amp\Future\await([$futureListening], new \Amp\SignalCancellation([SIGINT, SIGTERM]));
        \nostriphant\Functional\Iterator::walk($connections, fn(\Amp\Websocket\Client\WebsocketConnection $connection, int $index) => $connection->close());
    }
}
