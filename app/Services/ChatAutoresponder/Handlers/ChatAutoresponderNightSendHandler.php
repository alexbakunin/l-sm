<?php

namespace App\Services\ChatAutoresponder\Handlers;

use App\Models\ChatAutoresponder;
use App\Services\ChatAutoresponder\ChatAutoresponderService;
use App\Services\ChatMsg\ChatMsgService;
use Illuminate\Support\Collection;

/**
 * Class ChatAutoresponderNightSendHandler
 * Отправка сообщений о не рабочем времении
 *
 * @package App\Services\ChatAutoresponder\Handlers
 */
class ChatAutoresponderNightSendHandler
{
    /**
     * @var ChatMsgService
     */
    private ChatMsgService $chatMsgService;
    /**
     * @var ChatAutoresponderService
     */
    private ChatAutoresponderService $autoresponderService;
    /**
     * @var ChatAutoresponderSendHandler
     */
    private ChatAutoresponderSendHandler $sendHandler;

    /**
     * ChatAutoresponderNightSendHandler constructor.
     *
     * @param  ChatMsgService                $chatMsgService
     * @param  ChatAutoresponderService      $autoresponderService
     * @param  ChatAutoresponderSendHandler  $sendHandler
     */
    public function __construct(
        ChatMsgService $chatMsgService,
        ChatAutoresponderService $autoresponderService,
        ChatAutoresponderSendHandler $sendHandler
    ) {
        $this->chatMsgService = $chatMsgService;
        $this->autoresponderService = $autoresponderService;
        $this->sendHandler = $sendHandler;
    }

    /**
     * @param  Collection  $rooms
     * @param  string      $response
     */
    public function handle(Collection $rooms, string $response): void
    {
        $allMessages = $this->chatMsgService->getNotReadMessagesByRoomIds($rooms->pluck('room_id')->all());

        $sentResponses = $this->autoresponderService->getSentMessagesByMessageIds(
            $allMessages->pluck('id')->all(),
            ChatAutoresponder::TYPE_NIGHT
        );

        $list = $this->getListToSend($allMessages, $sentResponses);

        if (!$list) {
            return;
        }

        $this->send($list, $response, $allMessages);
    }

    /**
     * @param  Collection  $allMessages
     * @param  Collection  $sentResponses
     *
     * @return Collection
     */
    private function getListToSend(Collection $allMessages, Collection $sentResponses): Collection
    {
        $list = new Collection();
        $sentRooms = $sentResponses->pluck('room_id');

        foreach ($allMessages->pluck('room_id')->unique() as $roomId) {
            if ($sentRooms->search($roomId, true) === false) {
                $list->push($roomId);
            }
        }

        return $list;
    }

    /**
     * @param  Collection  $list
     * @param  string      $response
     * @param  Collection  $allMessages
     */
    private function send(Collection $list, string $response, Collection $allMessages): void
    {
        $groupedMessages = $allMessages->groupBy('room_id');
        foreach ($list as $roomId) {
            /** @var Collection $messages */
            $messages = $groupedMessages[$roomId];
            $this->sendHandler->handle($response, $roomId, $messages->first()->id, ChatAutoresponder::TYPE_NIGHT);
        }
    }
}
