<?php

declare(strict_types=1);

namespace App\Services\Queue\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuthorsCronSetRepository
{
    /** @var string */
    private const TABLE = 'authors_cron_set';

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->getBuilder()->count();
    }
}
