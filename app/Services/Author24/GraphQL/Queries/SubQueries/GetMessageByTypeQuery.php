<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetMessageByTypeQuery extends GraphQLQuery implements GraphQLQueryInterface
{
    private Query $query;

    public function __construct(string $type, array $selectionSet, bool $loadFiles = false)
    {
        if ($loadFiles) {
            $selectionSet[] = (new GetFilesQuery())->getQuery();
        }
        $this->query = (new Query('... on ' . $type))
            ->setSelectionSet(
                $selectionSet
            );

    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
