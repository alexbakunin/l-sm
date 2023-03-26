<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Models\Author24\Settings;
use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use App\Services\Author24\GraphQL\Queries\SubQueries\OrderInfoQuery;
use GraphQL\Query;
use GraphQL\RawObject;

class GetNewOrdersWithOutBid extends GraphQLQuery implements GraphQLQueryInterface
{
    private Query $query;
    private array $filter;
    private array $paginate;
    public const CATEGORIES_FILTER = [146, 158, 75, 1, 74, 2, 3, 4, 157, 73, 150, 139, 156, 5, 6, 113, 7, 81, 196, 147,
        8, 9, 11, 10, 76, 12, 138, 140, 127, 124, 13, 79, 15, 195, 80, 17, 16];

    public function __construct(Settings $settings, int $page = 0)
    {
        $this->filter = [
            'types'         => $settings->data['types_of_work'],
//            'categories'    => self::CATEGORIES_FILTER,
            'withoutMyBids' => true,
            'updatedFrom' => now()->subDay()->subHour()->timestamp
        ];
        \Log::debug('filter', $this->filter);
        $this->paginate = [
            'pageFrom' => $page,
            'pageTo'   => $page + 1
        ];
        $this->query = (new Query('orders'))
            ->setArguments([
                'filter'     => new RawObject($this->prepareRawObject($this->filter)),
                'pagination' => new RawObject($this->prepareRawObject($this->paginate)),
            ])
            ->setSelectionSet([
                'total',
                'pages',
                'filtered',
                'paginationCount',
                (new OrderInfoQuery())->getQuery()
            ]);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }
}
