<?php

namespace nostriphant\NIP01Tests;

use nostriphant\NIP01\Event;
use nostriphant\NIP01\Key;

class Functions {

    static function vectors_secp256k1(): object {
        return json_decode(file_get_contents(__DIR__ . '/vectors/ecdh-secp256k1.json'), false);
    }

    private static function key(string $hex): Key {
        return Key::fromHex($hex);
    }

    static function key_sender(): Key {
        return self::key('a71a415936f2dd70b777e5204c57e0df9a6dffef91b3c78c1aa24e54772e33c3');
    }

    static function pubkey_sender(): string {
        return self::key_sender()(Key::public());
    }

    static function key_recipient(): Key {
        return self::key('6eeb5ad99e47115467d096e07c1c9b8b41768ab53465703f78017204adc5b0cc');
    }

    static function pubkey_recipient(): string {
        return self::key_recipient()(Key::public());
    }

    static function event(array $event): Event {
        return new Event(...array_merge([
                    'id' => '',
                    'pubkey' => '',
                    'created_at' => time(),
                    'kind' => 1,
                    'content' => 'Hello World',
                    'sig' => '',
                    'tags' => []
                        ], $event));
    }
}
