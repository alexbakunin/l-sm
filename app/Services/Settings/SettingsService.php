<?php

namespace App\Services\Settings;

use App\Services\Settings\Repositories\SettingsRepository;

/**
 * Class SettingsService
 *
 * @package App\Services\Settings
 */
class SettingsService
{
    /**
     * @var SettingsRepository
     */
    private SettingsRepository $repository;

    /**
     * SettingsService constructor.
     *
     * @param SettingsRepository $repository
     */
    public function __construct(SettingsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить список настройке авто ответа по нормам
     *
     * @return array
     */
    public function getNormsSettings(): array
    {
        return $this->repository->getNormsSettings()->mapWithKeys(
            function ($item) {
                return [str_replace('autoresponder_norms_', '', $item->name) => $item->value];
            }
        )->toArray();
    }

    /**
     * @return array
     */
    public function getNorms2Settings(): array
    {
        return $this->repository->getNorms2Settings()->mapWithKeys(
            function ($item) {
                return [str_replace('autoresponder_norms2_', '', $item->name) => $item->value];
            }
        )->toArray();
    }

    /**
     * @return array
     */
    public function getNightSettings(): array
    {
        return $this->repository->getNightSettings()->mapWithKeys(
            function ($item) {
                return [str_replace('autoresponder_night_', '', $item->name) => $item->value];
            }
        )->toArray();
    }


    public function getMinimalOrdersCountForVip()
    {
        return $this->repository->getMinimalOrdersCountForVip()->first();
    }


    public function getMinimalOrdersTotalForVip()
    {
        return $this->repository->getMinimalOrdersTotalForVip()->first();
    }

    public function getAuthor24Enabled()
    {
        return $this->repository->getAuthor24Enabled();
    }
}
