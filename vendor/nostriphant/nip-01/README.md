# nip01
Nostr NIP-01 implementation in PHP, currently this contains only the private/public key related code

## Key usage

```
use nostriphant\NIP01\Key;

$private_key = Key::fromHex('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

$public_key = $private_key(Key::public());

$hash = hash('sha256', 'Hello World');
$signature = $private_key(Key::signer($hash));

if (Key::verify($public_key, $signature, $hash)) {
    echo 'Signature belongs to hash';
}
```


## Message usage

```
use nostriphant\NIP01\Message;

$message = Message::count(1);
print $message; // prints "['COUNT',1]"

```

```
use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;

$message = Message::event(new Event());
print $message; // prints "['EVENT',{...}]"

```
