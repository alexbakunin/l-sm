<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetOrderOfferQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    public function __construct(string $type = 'authorOffer')
    {
        $this->query = (new Query($type))->setSelectionSet([
            'id',
            'order_id',
            'bid',
            'origin_bid',
            'bid_diff',
        ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
