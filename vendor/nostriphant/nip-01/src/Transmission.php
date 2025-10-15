<?php

namespace nostriphant\NIP01;

interface Transmission {
    
    public function __invoke(Message $message) : bool;
    
}
