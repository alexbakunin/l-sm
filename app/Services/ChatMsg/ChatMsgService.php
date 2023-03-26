<?php

namespace App\Services\ChatMsg;

use App\Services\ChatMsg\Repositories\ChatMsgRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class ChatMsgService
 *
 * @package App\Services\ChatMsg
 */
class ChatMsgService
{
    /**
     * @var ChatMsgRepository
     */
    private ChatMsgRepository $repository;

    /**
     * ChatMsgService constructor.
     *
     * @param  ChatMsgRepository  $repository
     */
    public function __construct(ChatMsgRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Получить id комнат с непрочитанными сообщениями за период
     *
     * @param  Carbon  $fromTime
     *
     * @return Collection
     */
    public function getNotReadRoomsByTime(Carbon $fromTime): Collection
    {
        return $this->repository->getNotReadRoomsByTime($fromTime);
    }

    /**
     * Получить все непрочитанные сообщения по id комнат
     *
     * @param  array  $roomIds
     *
     * @return Collection
     */
    public function getNotReadMessagesByRoomIds(array $roomIds): Collection
    {
        return $this->repository->getNotReadMessagesByRoomIds($roomIds);
    }

    /**
     * Отправляем сообщение в чат
     *
     * @param  int     $roomId
     * @param  string  $message
     *
     * @return int
     */
    public function send(int $roomId, string $message): int
    {
        return $this->repository->send($roomId, $message);
    }
}
