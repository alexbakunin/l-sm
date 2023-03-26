<?php

namespace App\Services\Author24\GraphQL;

use GraphQL\Mutation;

interface GraphQLMutationInterface
{
    public function getMutation(): Mutation;
}
