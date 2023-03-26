<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use App\Services\Author24\GraphQL\Queries\SubQueries\GetWorkTypeSubgroupInfoQuery;
use GraphQL\Query;

class GetNotificationsQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    private Query $query;
    private       $limit;
    private       $offset;

    public function __construct($offset = 0, $limit = 100)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->query = (new Query('getNotifications'))
            ->setArguments([
                'limit'  => $this->limit,
                'offset' => $this->offset,
            ])
            ->setSelectionSet([
            'id',
            'type',
            'label',
            'message',
            'date',
            'isRead'
        ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
