<?php

namespace nostriphant\Client;

readonly class SubscriptionFactory {
    
    private \Closure $speak;
    
    public function __construct(callable $speak, private \nostriphant\Functional\Index $subscriptions) {
        $this->speak = \Closure::fromCallable($speak);
    }
    
    public function __invoke(?array $authors = null, ?array $ids = null, ?array $kinds = null, ?int $until = null, ?int $since = null, ?int $limit = null, array ...$tags) {
        $id = bin2hex(random_bytes(32));
        $filters = array_filter([
            'authors'=>$authors, 
            'ids'=>$ids, 
            'kinds'=>$kinds, 
            'until'=>$until, 
            'since'=>$since, 
            'limit'=>$limit
        ], fn($val) => !is_null($val));
        
        foreach ($tags as $tag => $tag_filter) {
            $filters[ltrim($name, '#')] = $tag_filter;
        }
        
        return new Subscription($id, $this->subscriptions, $this->speak, $filters);
    }
     
}
