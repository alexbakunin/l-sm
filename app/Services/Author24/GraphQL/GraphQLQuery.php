<?php

namespace App\Services\Author24\GraphQL;

class GraphQLQuery
{
    protected function prepareRawObject(array $data)
    {
        return preg_replace('/"([^"]+)"\s*:\s*/', '$1:', json_encode($data));
    }
}
