<?php

namespace App\Services\Author24\GraphQL\Queries;

use GraphQL\Query;

class GetProfile
{
    private Query $query;

    public function __construct()
    {
        $this->query = (new Query('profile'))->setSelectionSet([
            'id',
            'balance',
            'balanceReal',
            'balanceVirtual'
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
