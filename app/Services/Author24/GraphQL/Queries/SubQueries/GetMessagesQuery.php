<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetMessagesQuery extends GraphQLQuery implements GraphQLQueryInterface
{
    private Query $query;

    public function __construct()
    {
        $this->query = (new Query('messages'))
            ->setSelectionSet(
                [
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
                ]
            );
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
