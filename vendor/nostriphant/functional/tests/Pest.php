<?php

namespace nostriphant\FunctionalTests;

use function expect;

class Pest {
    static function extend(callable $expect) {
        $expect()->extend('toHaveState', function (mixed ...$expected_states) use ($expect) {
            $state_mocks = array_map(fn(mixed $expected_args) => new class($expected_args) {

                        public bool $invoked = false;

                        public function __construct(private array|string $expected_args) {

                        }

                        public function __invoke(mixed ...$args): \Generator {
                            if (is_array($this->expected_args)) {
                                foreach ($this->expected_args as $index => $expected_arg) {
                                    expect($args[$index])->toBe($expected_arg);
                                }
                            } elseif ($this->expected_args === '*') {
                                // accept everything!
                            }
                            $this->invoked = true;
                            yield 0;
                        }
                    }, $expected_states);

            foreach (call_user_func_array($this->value, $state_mocks) as $msg) {

            }
            $expect(array_reduce($state_mocks, fn(bool $carry, $mock) => $mock->invoked || $carry, false))->toBeTrue();
        });
    }
}