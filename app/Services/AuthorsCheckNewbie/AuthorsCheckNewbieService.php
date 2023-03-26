<?php

declare(strict_types=1);

namespace App\Services\AuthorsCheckNewbie;

use App\Services\AuthorsCheckNewbie\Repositories\AuthorsRepository;
use App\Services\AuthorsCheckNewbie\Repositories\OrdersRepository;
use Illuminate\Support\Collection;

class AuthorsCheckNewbieService
{
    /** @var int */
    private const NEWBIE_CAPACITY_DEFAULT = 2;
    /**
     * @var AuthorsRepository
     */
    private AuthorsRepository $authorsRepository;
    /**
     * @var OrdersRepository
     */
    private OrdersRepository $ordersRepository;

    /**
     * @param AuthorsRepository $authorsRepository
     * @param OrdersRepository  $ordersRepository
     */
    public function __construct(AuthorsRepository $authorsRepository, OrdersRepository $ordersRepository)
    {
        $this->authorsRepository = $authorsRepository;
        $this->ordersRepository = $ordersRepository;
    }

    /**
     * @return Collection
     */
    public function getNewbieAuthors(): Collection
    {
        return $this->authorsRepository->getNewbieAuthors();
    }

    /**
     * @param Collection $authorIds
     * @return int[]
     */
    public function getCountEndedAuthorsOrders(Collection $authorIds): array
    {
        $list = $this->ordersRepository->getEndedOrdersByAuthorIds();

        $result = [];
        foreach ($list as $item) {
            if ($authorIds->search($item->author_id) === false) {
                continue;
            }
            if (!isset($result[$item->author_id])) {
                $result[$item->author_id] = 0;
            }
            $result[$item->author_id]++;
        }

        return $result;
    }

    /**
     * @param int      $id
     * @param int|null $capacity
     * @return bool
     */
    public function clearNewbie(int $id, ?int $capacity): bool
    {
        return $this->authorsRepository->update(
            $id,
            [
                'is_newbie' => 0,
                'capacity' => $capacity === 2 ? null : $capacity,
                'trust' => 1,
            ]
        );
    }

    /**
     * @param int $id
     * @return bool
     */
    public function setNewbieCapacity(int $id): bool
    {
        return $this->authorsRepository->update($id, ['capacity' => self::NEWBIE_CAPACITY_DEFAULT]);
    }
}
