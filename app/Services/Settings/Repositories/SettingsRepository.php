<?php

namespace App\Services\Settings\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class SettingsRepository
 *
 * @package App\Services\Settings\Repositories
 */
class SettingsRepository
{
    /** @var string */
    private const TABLE = 'settings_vars';

    /**
     * @return Collection
     */
    public function getNormsSettings(): Collection
    {
        return $this->getBuilder()
            ->where('name', 'like', 'autoresponder\_norms\_%')->get();
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')
            ->table(self::TABLE);
    }

    /**
     * @return Collection
     */
    public function getNorms2Settings(): Collection
    {
        return $this->getBuilder()
            ->where('name', 'like', 'autoresponder\_norms2\_%')->get();
    }

    /**
     * @return Collection
     */
    public function getNightSettings(): Collection
    {
        return $this->getBuilder()
            ->where('name', 'like', 'autoresponder\_night\_%')->get();
    }

    public function getMinimalOrdersCountForVip()
    {
        return $this->getBuilder()
            ->where('name', '=', 'client_vip_orders_count')->get();
    }

    public function getMinimalOrdersTotalForVip()
    {
        return $this->getBuilder()
            ->where('name', '=', 'client_vip_orders_total')->get();
    }

    public function getAuthor24Enabled()
    {
        return $this->getBuilder()
            ->where('name', '=', 'external_author24_enabled')->first();
    }
}
