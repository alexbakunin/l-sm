<?php

declare(strict_types=1);

namespace App\Services\AuthorsCheckNewbie\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuthorsRepository
{
    /** @var string */
    private const TABLE = 'users';

    /**
     * @return Collection
     */
    public function getNewbieAuthors(): Collection
    {
        return $this->getBuilder()
            ->select(['id', 'is_newbie', 'capacity'])
            ->where('is_newbie', 1)
            ->get();
    }


    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('wizard')->table(self::TABLE);
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
