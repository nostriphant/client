<?php

namespace nostriphant\NIP01;

readonly class Message {

    public mixed $payload;

    public function __construct(public string $type, mixed ...$payload) {
        $this->payload = $payload;
    }

    public function __invoke(): array {
        $payload = $this->payload;
        array_unshift($payload, $this->type);
        return $payload;
    }

    public function __toString(): string {
        return Nostr::encode($this());
    }

    static function decode(string $json): self {
        return new self(...Nostr::decode($json));
    }

    static function __callStatic(string $name, array $arguments): self {

        return new self(strtoupper($name), ...array_map(function (mixed $argument) {
                    if ($argument instanceof Event) {
                        return $argument();
                    } else {
                        return $argument;
                    }
                }, $arguments));
    }
}
