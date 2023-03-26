<?php

namespace App\Services\ChatAutoresponder;

use App\Services\ChatAutoresponder\Repositories\ChatAutoresponderRepository;
use Illuminate\Support\Collection;

/**
 * Class ChatAutoresponderService
 *
 * @package App\Services\ChatAutoresponder
 */
class ChatAutoresponderService
{
    /**
     * @var ChatAutoresponderRepository
     */
    private ChatAutoresponderRepository $repository;

    /**
     * ChatAutoresponderService constructor.
     *
     * @param  ChatAutoresponderRepository  $repository
     */
    public function __construct(ChatAutoresponderRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить список отправленных автоответов по ID сообщений
     *
     * @param  array   $messageIds
     * @param  string  $type
     *
     * @return Collection
     */
    public function getSentMessagesByMessageIds(array $messageIds, string $type): Collection
    {
        return $this->repository->getSentMessagesByMessageIds($messageIds, $type);
    }
}
