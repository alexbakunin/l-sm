<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Models\Author24\Order;
use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use App\Services\Author24\GraphQL\Queries\SubQueries\GetMessagesQuery;
use App\Services\Author24\GraphQL\Queries\SubQueries\OrderInfoQuery;
use GraphQL\Query;

class GetOrderDialogQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    private Query $query;
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;

        $this->query = (new Query('dialog'))
            ->setArguments([
                'orderId' => $this->order->order_id,
            ])
            ->setSelectionSet([
                'canComment',
                'canUploadFile',
                'lastCommentId',
                'countUnreadMessages',
                (new OrderInfoQuery('order', []))->getQuery(),
                (new GetMessagesQuery())->getQuery()
            ]);

    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
