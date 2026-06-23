<?php

namespace nostriphant\Client;

final readonly class Subscription {
    
    private \Closure $speak;
    
    public function __construct(public string $id, private \nostriphant\Functional\Index $subscriptions, callable $speak, private array $filters) {
        $this->speak = \Closure::fromCallable($speak);
    }
    
    public function __invoke(callable $reply) {
        $this->subscriptions[$this->id] = fn(\nostriphant\NIP01\Event $event, callable $stop) => $reply($event, fn() => ($this->speak)(\nostriphant\NIP01\Message::close($this->id)), $stop);
        return ($this->speak)(\nostriphant\NIP01\Message::req($this->id, $this->filters));
    }
    
    
}
