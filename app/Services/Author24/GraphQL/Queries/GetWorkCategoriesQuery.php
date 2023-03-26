<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Services\Author24\GraphQL\Queries\SubQueries\GetWorkCategoryItemsQuery;
use GraphQL\Query;

class GetWorkCategoriesQuery extends \App\Services\Author24\GraphQL\GraphQLQuery
    implements \App\Services\Author24\GraphQL\GraphQLQueryInterface
{

    public function __construct()
    {
        $this->query = (new Query('workcategoriesgroup'))->setSelectionSet([
            'name',
            (new GetWorkCategoryItemsQuery())->getQuery()
        ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
