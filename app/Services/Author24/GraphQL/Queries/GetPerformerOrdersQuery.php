<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use App\Services\Author24\GraphQL\Queries\SubQueries\OrderInfoQuery;
use GraphQL\Query;

class GetPerformerOrdersQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    private int   $limit;
    private int   $offset;
    private Query $query;

    public function __construct(int $limit, int $offset)
    {
        $this->limit = $limit;
        $this->offset = $offset;


        $this->query = (new Query('getPerformerOrders'))
            ->setArguments([
                'limit'  => $this->limit,
                'offset' => $this->offset,
            ])
            ->setSelectionSet([
                'total',
                (new OrderInfoQuery())->getQuery()
            ]);

    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
