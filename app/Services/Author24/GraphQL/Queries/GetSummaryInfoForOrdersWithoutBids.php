<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Models\Author24\Settings;
use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;
use GraphQL\RawObject;

class GetSummaryInfoForOrdersWithoutBids extends GraphQLQuery implements GraphQLQueryInterface
{
    public function __construct(Settings $settings)
    {
        $this->filter = [
            'types'         => $settings->data['types_of_work'],
            'categories'    => GetNewOrdersWithOutBid::CATEGORIES_FILTER,
            'withoutMyBids' => true
        ];

        $this->query = (new Query('orders'))
            ->setArguments([
                'filter' => new RawObject($this->prepareRawObject($this->filter)),
            ])
            ->setSelectionSet([
                'total',
                'pages',
            ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
