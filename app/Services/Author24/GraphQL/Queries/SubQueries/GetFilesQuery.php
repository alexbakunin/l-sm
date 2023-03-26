<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetFilesQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    public function __construct(string $type = 'files')
    {
        $this->query = (new Query($type))->setSelectionSet([
            'id',
            'name',
            'type',
            'path',
            'sizeInMb',
            'isFinal',
            'readableCreation'
        ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
