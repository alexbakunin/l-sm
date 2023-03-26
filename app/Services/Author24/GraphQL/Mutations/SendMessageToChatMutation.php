<?php

namespace App\Services\Author24\GraphQL\Mutations;

use App\Models\Author24\Order;
use App\Services\Author24\GraphQL\GraphQLMutationInterface;
use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\Queries\SubQueries\GetFilesQuery;
use App\Services\Author24\GraphQL\Queries\SubQueries\GetMessageByTypeQuery;
use App\Services\Author24\GraphQL\Queries\SubQueries\GetMessagesQuery;
use GraphQL\Mutation;

class SendMessageToChatMutation extends GraphQLQuery implements GraphQLMutationInterface
{
    private Mutation $mutation;

    public function __construct(Order $order, string $text)
    {
        $this->mutation = (new Mutation('addComment'))
            ->setArguments([
                'orderId'    => $order->order_id,
                'text'       => $text,
                'fileStatus' => 0,
            ])->setSelectionSet([
                    '__typename',
                    (new GetMessageByTypeQuery('system', [
                        'id',
                        'creation',
                        'type',
                        'text',
                        'readableCreation'
                    ]))->getQuery(),
                    (new GetMessageByTypeQuery('message', [
                        'id',
                        'user_id',
                        'text',
                        'creation',
                        'isRead',
                        'readableCreation',
                        'isAdminComment',
                        (new GetFilesQuery())->getQuery()
                    ]))->getQuery(),
                    (new GetMessageByTypeQuery('correction', [
                        'id',
                        'user_id',
                        'text',
                        'creation',
                        'isRead',
                        'readableCreation',
                        'isAdminComment',
                        (new GetFilesQuery())->getQuery()
                    ]))->getQuery(),
                    (new GetMessageByTypeQuery('recommendation', [
                        'id',
                        'user_id',
                        'text',
                        'creation',
                        'readableCreation',
                        'isAdminComment',
                        'isRead',
                        'watched',
                        'promoUrl',
                        'isMobile',
                    ]))->getQuery(),
                    (new GetMessageByTypeQuery('pricerequest', [
                        'id',
                        'user_id',
                        'text',
                        'creation',
                        'readableCreation',
                        'isAdminComment',
                        'isRead',
                        'watched',
                        'isMobile',
                    ]))->getQuery(),
                    (new GetMessageByTypeQuery('assistant', [
                        'id',
                        'text',
                        'creation',
                        'isRead',
                        'type',
                    ]))->getQuery(),
            ]);
    }

    public function getMutation(): Mutation
    {
        return $this->mutation;
    }
}
