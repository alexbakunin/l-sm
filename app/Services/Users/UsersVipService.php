<?php
declare(strict_types=1);

namespace App\Services\Users;

use App\Services\AuthorsCheckNewbie\Repositories\OrdersRepository;
use App\Services\Users\Repositories\UsersRepository;

class UsersVipService
{
    /**
     * @var UsersRepository
     */
    private UsersRepository  $usersRepository;
    private OrdersRepository $ordersRepository;

    public function __construct(UsersRepository $usersRepository, OrdersRepository $ordersRepository)
    {
        $this->usersRepository = $usersRepository;
        $this->ordersRepository = $ordersRepository;
    }


    public function getTotalPaysOrderByUserId()
    {
        return $this->ordersRepository->getTotalPaysOrderByUserId();
    }

    public function getAllUsers()
    {
        return $this->usersRepository->getAllUsers();
    }

    public function setVipStatus(int $userId)
    {
        return $this->usersRepository->update(
            $userId,
            [
                'vip_flag' => 1,
            ]
        );
    }

    public function deleteVipStatus(int $userId)
    {
        return $this->usersRepository->update(
            $userId,
            [
                'vip_flag' => 0,
            ]
        );
    }
}
