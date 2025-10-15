<?php

namespace nostriphant\NIP01;

use Elliptic\EC;

readonly class Key {

    public function __construct(#[\SensitiveParameter] private string $private_key) {
        
    }

    public function __invoke(callable $input): mixed {
        return $input($this->private_key);
    }

    static function fromHex(#[\SensitiveParameter] string $private_key): callable {
        return new self($private_key);
    }

    static function curve(): EC {
        return new EC('secp256k1');
    }

    static function generate(): callable {
        $config = [
            //"config" => getenv('OPENSSL_CONF'),
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp256k1'
        ];
        $keypair = openssl_pkey_new($config);
        $details = openssl_pkey_get_details($keypair);
        return new self(implode(unpack("H*", $details['ec']['d'])));
    }

    static function signer(string $hash): callable {
        return fn(string $private_key) => secp256k1_nostr_sign($private_key, $hash);
    }

    static function verify(string $pubkey, string $signature, string $hash): bool {
        return secp256k1_nostr_verify($pubkey, $hash, $signature);
    }

    static function sharedSecret(#[\SensitiveParameter] string $recipient_pubkey) {
        return function (#[\SensitiveParameter] string $private_key) use ($recipient_pubkey): bool|string {
            $ec = self::curve();
            try {
                $key1 = $ec->keyFromPrivate($private_key, 'hex');
                $pub2 = $ec->keyFromPublic($recipient_pubkey, 'hex')->pub;
                return $key1->derive($pub2)->toString('hex');
            } catch (\Exception $e) {
                throw new \InvalidArgumentException($e->getMessage());
            }
        };
    }

    static function public(): callable {
        return fn(#[\SensitiveParameter] string $private_key): string => substr(self::curve()->keyFromPrivate($private_key)->getPublic(true, 'hex'), 2);
    }
    static function private(): callable {
        return fn(#[\SensitiveParameter] string $private_key): string => $private_key;
    }
}
