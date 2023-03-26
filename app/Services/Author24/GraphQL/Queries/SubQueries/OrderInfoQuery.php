<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class OrderInfoQuery extends GraphQLQuery implements GraphQLQueryInterface
{
    private Query $query;
    private array $defaultSelection;
    public function __construct(string $select = 'orders', array $appendSelection = ['font', 'unique'])
    {
        $this->defaultSelection = [
            'id',
            'title',
            'description',
            'budget',
            'recommendedBudget',
            'pagesFrom',
            'pagesTo',
            'pages',
            'authorHasOffer',
            'creation',
            'deadline',
            'interval',
            'actions',
            'deadlineBeenPercent',
            (new GetOrderFieldInfoQuery('type'))->getQuery(),
            (new GetOrderFieldInfoQuery('stage'))->getQuery(),
            (new GetOrderFieldInfoQuery('category'))->getQuery(),
            (new GetOrderFieldInfoQuery('uniqueService', ['name', 'url']))->getQuery(),
            (new GetFilesQuery('customerFiles'))->getQuery(),
            (new GetFilesQuery('authorFiles'))->getQuery(),
        ];
        $selectionSet = array_merge($this->defaultSelection, $appendSelection);
        $this->query = (new Query($select))->setSelectionSet($selectionSet);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getDefaultSelection(): array
    {
        return $this->defaultSelection;
    }


}
