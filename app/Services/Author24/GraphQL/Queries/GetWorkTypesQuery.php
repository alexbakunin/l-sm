<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use App\Services\Author24\GraphQL\Queries\SubQueries\GetWorkTypeSubgroupInfoQuery;
use GraphQL\Query;

class GetWorkTypesQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    private Query $query;

    public function __construct()
    {
        $this->query = (new Query('worktypes'))->setSelectionSet([
            'id',
            'name',
            (new GetWorkTypeSubgroupInfoQuery())->getQuery()
        ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
