<?php

declare(strict_types=1);

namespace App\Services\CrmEmail\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CrmEmailSubscribeRepository
{
    /** @var string */
    private const TABLE = 'users';

    /**
     * @param  string  $email
     * @param  int     $officeId
     *
     * @return bool
     */
    public function checkSubscribe(string $email, int $officeId): bool
    {
        return (bool)($this->getBuilder()
                ->select(['subscribe'])
                ->where('email', '=', $email)
                ->where('office_id', '=', $officeId)
                ->get()->first()->subscribe ?? false);
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }
}
