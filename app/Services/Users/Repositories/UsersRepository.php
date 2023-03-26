<?php

declare(strict_types=1);

namespace App\Services\Users\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UsersRepository
{
    /** @var string */
    private const TABLE = 'users';

    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return $this->getBuilder()
            ->select(['id', 'vip_flag'])
            ->where('active', '=', 1)
            ->get();
    }


    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }

    /**
     * @param int   $id
     * @param array $values
     * @return bool
     */
    public function update(int $id, array $values): bool
    {
        return (bool)$this->getBuilder()->where('id', '=', $id)->update($values);
    }


}
