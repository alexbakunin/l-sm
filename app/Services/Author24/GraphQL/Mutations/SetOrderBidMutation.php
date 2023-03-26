<?php

namespace App\Services\Author24\GraphQL\Mutations;

use App\Models\Author24\Order;
use App\Models\Author24\Template;
use App\Services\Author24\GraphQL\GraphQLMutationInterface;
use App\Services\Author24\GraphQL\GraphQLQuery;
use GraphQL\Mutation;

class SetOrderBidMutation extends GraphQLQuery implements GraphQLMutationInterface
{
    private Mutation $mutation;

    public function __construct(Order $order, bool $withText = true)
    {
        $message = '';
        if ($withText) {
            $template = Template::where('account_id', $order->account_id)->inRandomOrder()->first();
            $message = $template->text;
        }
        $this->mutation = (new Mutation('orderCreateOffer'))
            ->setArguments([
                'id'        => $order->order_id,
                'bid'       => $order->price,
                'message'   => $message,
                'subscribe' => true
            ])
            ->setSelectionSet(
                [
                    'id', 'order_id', 'bid', 'origin_bid', 'our_tax', 'bid_diff'
                ]
            );
    }

    public function getMutation(): Mutation
    {
        return $this->mutation;
    }
}
