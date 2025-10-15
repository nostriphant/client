<?php

namespace nostriphant\Functional;

readonly class Await {

    public function __construct(private \Closure $what) {
        
    }

    public function __invoke(callable $callback): mixed {
        return $callback(call_user_func($this->what));
    }
}
