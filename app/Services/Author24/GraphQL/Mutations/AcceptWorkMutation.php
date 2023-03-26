<?php

namespace App\Services\Author24\GraphQL\Mutations;

use App\Models\Author24\Account;
use App\Models\Author24\Order;
use App\Services\Author24\GraphQL\GraphQLMutationInterface;
use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\Queries\SubQueries\OrderInfoQuery;
use GraphQL\Mutation;

class AcceptWorkMutation extends GraphQLQuery implements GraphQLMutationInterface
{
    private Mutation $mutation;

    public function __construct(Order $order)
    {
        $this->mutation = (new Mutation('acceptWork'))
            ->setArguments([
                'orderId' => $order->order_id
            ])
            ->setSelectionSet(
                    (new OrderInfoQuery('order'))->getDefaultSelection()
            );
    }

    /**
     * @return Mutation
     */
    public function getMutation(): Mutation
    {
        return $this->mutation;
    }


}
