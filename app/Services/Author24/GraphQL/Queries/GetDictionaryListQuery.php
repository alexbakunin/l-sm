<?php

namespace App\Services\Author24\GraphQL\Queries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetDictionaryListQuery extends GraphQLQuery implements GraphQLQueryInterface
{
    private const DICTIONARY_TYPES_LIST = [
        'phonemasks',
        'worktypes',
        'workcategoriesgroup',
        'workcategories',
        'questions',
        'ordersStages',
        'filesStatus',
        'autoBids',
        'reworkstages',
        'dictionariesforcategorycustomproperties',
        'programlanguages',
        'taskcomplexities',
        'translatelanguagedict',
        'projectcomplexities',
        'educationCategories',
    ];

    public function __construct(string $type)
    {
        if (!in_array($type, self::DICTIONARY_TYPES_LIST)) {
            throw new \Exception('Некорректный тип справочника');
        }
        $this->query = (new Query('dictionarylist'));
        switch ($type) {
            case 'worktypes':
                $this->query->setSelectionSet([(new GetWorkTypesQuery())->getQuery()]);
                break;
            case 'workcategories':
                $this->query->setSelectionSet([(new GetWorkCategoriesQuery())->getQuery()]);
                break;
        }
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
