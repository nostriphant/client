# functional-alternate
Functional helper classes for nostriphant projects

## Usage

```
use nostriphant\Functional\Alternate;

$state = Alternate::state1('Hello World!');

$evaluate = $state(state1: function (string $message) {
    yield $message;
});

foreach ($evaluate as $message) {
    expect($message)->toBe('Hello World!');
}

```