<?php

namespace App\Services\ChatMsg\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class ChatMsgRepository
 *
 * @package App\Services\ChatMsg\Repositories
 */
class ChatMsgRepository
{
    /** @var string */
    private const TABLE = 'chat_msg';

    /**
     * @param  Carbon  $fromTime
     *
     * @return Collection
     */
    public function getNotReadRoomsByTime(Carbon $fromTime): Collection
    {
        return $this->getBuilder()
            ->select(['room_id'])
            ->where('read', '=', 0)
            ->where('active', '=', 1)
            ->where('create', '>=', $fromTime->timestamp)
            ->where('from_table', '=', 'users')
            ->groupBy(['room_id'])
            ->get();
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }

    /**
     * @param  array  $roomIds
     *
     * @return Collection
     */
    public function getNotReadMessagesByRoomIds(array $roomIds): Collection
    {
        return $this->getBuilder()
            ->select(['id', 'room_id', 'create'])
            ->where('read', '=', 0)
            ->where('active', '=', 1)
            ->whereIn('room_id', $roomIds)
            ->where('from_table', '=', 'users')
            ->orderBy('create', 'desc')
            ->get();
    }

    /**
     * @param  int     $roomId
     * @param  string  $message
     *
     * @return int
     */
    public function send(int $roomId, string $message): int
    {
        return $this->getBuilder()->insertGetId(
            [
                'room_id'    => $roomId,
                'from_table' => 'staff',
                'from_id'    => 0,
                'to_table'   => '',
                'to_id'      => 0,
                'files'      => '',
                'read'       => 0,
                'from_staff' => 0,
                'text'       => $message,
                'create'     => time(),
                'active'     => 1,
            ]
        );
    }

}
