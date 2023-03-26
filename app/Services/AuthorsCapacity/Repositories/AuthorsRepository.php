<?php

declare(strict_types=1);

namespace App\Services\AuthorsCapacity\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuthorsRepository
{
    /** @var string */
    private const TABLE = 'users';

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('wizard')->table(self::TABLE);
    }

    /**
     * @param int $id
     * @param int $currentCapacity
     * @return bool
     */
    public function updateCurrentCapacity(int $id, int $currentCapacity): bool
    {
        return (bool)$this->getBuilder()->where('id', '=', $id)->update(['current_capacity' => $currentCapacity]);
    }

    /**
     * @return Collection
     */
    public function findAuthorsWithCapacity(): Collection
    {
        return $this->getBuilder()
            ->select(['id', 'capacity', 'current_capacity'])
            ->where('capacity', '>', 0)
            ->get();
    }
}
