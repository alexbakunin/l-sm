<?php

namespace App\Services\Author24\GraphQL\Mutations;

use App\Models\Author24\Account;
use App\Services\Author24\GraphQL\GraphQLMutationInterface;
use App\Services\Author24\GraphQL\GraphQLQuery;
use GraphQL\Mutation;

class LoginMutation extends GraphQLQuery implements GraphQLMutationInterface
{
    private Mutation $mutation;

    public function __construct(Account $account)
    {
        $this->mutation = (new Mutation('login'))
            ->setArguments([
                'apiKey'   => config('author24.graphql.key'),
                'login'    => $account->email,
                'password' => $account->password
            ])
            ->setSelectionSet(
                [
                    'token',
                ]
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
