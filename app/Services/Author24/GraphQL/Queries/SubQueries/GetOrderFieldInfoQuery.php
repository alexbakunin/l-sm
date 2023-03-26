<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetOrderFieldInfoQuery extends GraphQLQuery implements GraphQLQueryInterface
{
    private Query $query;

    public function __construct(string $field, array $fields = [])
    {
        $fields = $fields
            ?: ['id', 'name'];
        $this->query = (new Query($field))->setSelectionSet($fields);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
