<?php

namespace App\Console\Commands;

use App\Services\Settings\SettingsService;
use App\Services\Users\UsersVipService;
use Illuminate\Console\Command;

class CheckUsersVIP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:check-vip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проставляем статус VIP по количеству оплаченных заказов и сумме оплат';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(UsersVipService $usersVipService, SettingsService $settingsService)
    {
        $userTotals = $usersVipService->getTotalPaysOrderByUserId();
        $users = $usersVipService->getAllUsers()->keyBy('id');
        $this->getOutput()->info(sprintf('Найдено активных пользователей: %d', $users->count()));
        $this->getOutput()->info(sprintf('Найдено пользователей с оплатами: %d', $userTotals->count()));

        $minimalOrdersCount = (int) $settingsService->getMinimalOrdersCountForVip()?->value ?: 0;
        $minimalOrdersTotal = (int) $settingsService->getMinimalOrdersTotalForVip()?->value ?: 0;

        $stat = ['users_count' => $users->count(), 'users_totals' => $userTotals->count(), 'vipped' => 0, 'unvipped' => 0];

        if (!$userTotals->count() || ($minimalOrdersTotal == 0 && !$minimalOrdersCount == 0)) {
            return 0;
        }

        foreach ($userTotals as $total) {
            if (!isset($users[$total->user_id])) {
                continue;
            }
            if ((int) $total->sum > $minimalOrdersTotal && (int) $total->count > $minimalOrdersCount) {
                $stat['vipped']++;
                $usersVipService->setVipStatus($total->user_id);
            } else {
                $stat['unvipped']++;
                $usersVipService->deleteVipStatus($total->user_id);
            }
        }

        $this->getOutput()->info(
            sprintf('VIP пользователей: %d | Обычных пользователей: %d', $stat['vipped'], $stat['unvipped'])
        );
    }
}
