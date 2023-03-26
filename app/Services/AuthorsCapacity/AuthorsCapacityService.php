<?php

declare(strict_types=1);

namespace App\Services\AuthorsCapacity;

use App\Services\AuthorsCapacity\Repositories\AuthorsRepository;
use App\Services\AuthorsCapacity\Repositories\OrdersRepository;
use Illuminate\Support\Collection;

class AuthorsCapacityService
{
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
    public function findAuthorsWithCapacity(): Collection
    {
        return $this->authorsRepository->findAuthorsWithCapacity();
    }

    /**
     * @param Collection $authorIds
     * @return int[]
     */
    public function getProcessOrdersByAuthorIds(Collection $authorIds): array
    {
        $list = $this->ordersRepository->getProcessOrdersByAuthorIds();

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
     * @param int $id
     * @param int $capacity
     * @return bool
     */
    public function setCurrentCapacity(int $id, int $capacity): bool
    {
        return $this->authorsRepository->updateCurrentCapacity($id, $capacity);
    }
}
