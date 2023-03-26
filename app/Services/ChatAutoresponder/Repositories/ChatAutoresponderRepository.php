<?php

namespace App\Services\ChatAutoresponder\Repositories;

use App\Models\ChatAutoresponder;
use Illuminate\Support\Collection;

/**
 * Class ChatAutoresponderRepository
 *
 * @package App\Services\ChatAutoresponder\Repositories
 */
class ChatAutoresponderRepository
{

    /**
     * @param  array   $messageIds
     * @param  string  $type
     *
     * @return Collection
     */
    public function getSentMessagesByMessageIds(array $messageIds, string $type): Collection
    {
        return ChatAutoresponder::whereIn('message_id', $messageIds)
            ->where('type', '=', $type)
            ->get();
    }

    /**
     * @param  int     $roomId
     * @param  int     $messageId
     * @param  int     $responseMessageId
     * @param  string  $type
     *
     * @return bool
     */
    public function send(int $roomId, int $messageId, int $responseMessageId, string $type): bool
    {
        $item = new ChatAutoresponder();
        $item->room_id = $roomId;
        $item->message_id = $messageId;
        $item->response_message_id = $responseMessageId;
        $item->type = $type;

        return $item->save();
    }
}
