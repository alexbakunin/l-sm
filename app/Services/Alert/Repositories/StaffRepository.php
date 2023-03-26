<?php

declare(strict_types=1);

namespace App\Services\Alert\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffRepository
{
    /** @var string */
    private const TABLE = 'staff';

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAdminTelegramIds(): Collection
    {
        return $this->getBuilder()->select(['telegram_id'])
            ->where('active', '=', 1)
            ->where('group', '=', 1)
            ->where('vacation', '=', 0)
            ->where('telegram_support_notice', '=', 1)
            ->where('telegram_id', '>', 0)
            ->get();
    }
}
