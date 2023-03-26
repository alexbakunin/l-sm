<?php

namespace App\Services\ChatAutoresponder\Handlers;

use App\Models\ChatAutoresponder;
use App\Services\ChatAutoresponder\ChatAutoresponderService;
use App\Services\ChatMsg\ChatMsgService;
use App\Services\OrdersLog\OrdersLogService;
use App\Services\OrdersLogStatus\OrdersLogStatusService;
use Illuminate\Support\Collection;

/**
 * Class ChatAutoresponderNormsSendHandler
 *
 * @package App\Services\ChatAutoresponder\Handlers
 */
class ChatAutoresponderNormsSendHandler
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
     * @var \App\Services\OrdersLog\OrdersLogService
     */
    private OrdersLogService $ordersLogService;
    /**
     * @var \App\Services\OrdersLogStatus\OrdersLogStatusService
     */
    private OrdersLogStatusService $ordersLogStatusService;
    /**
     * @var \App\Services\ChatAutoresponder\Handlers\ChatAutoresponderSendHandler
     */
    private ChatAutoresponderSendHandler $sendHandler;

    /**
     * ChatAutoresponderNormsSendHandler constructor.
     *
     * @param  ChatMsgService                                                         $chatMsgService
     * @param  \App\Services\OrdersLog\OrdersLogService                               $ordersLogService
     * @param  \App\Services\OrdersLogStatus\OrdersLogStatusService                   $ordersLogStatusService
     * @param  ChatAutoresponderService                                               $autoresponderService
     * @param  \App\Services\ChatAutoresponder\Handlers\ChatAutoresponderSendHandler  $sendHandler
     */
    public function __construct(
        ChatMsgService $chatMsgService,
        OrdersLogService $ordersLogService,
        OrdersLogStatusService $ordersLogStatusService,
        ChatAutoresponderService $autoresponderService,
        ChatAutoresponderSendHandler $sendHandler
    ) {
        $this->chatMsgService = $chatMsgService;
        $this->autoresponderService = $autoresponderService;
        $this->ordersLogService = $ordersLogService;
        $this->ordersLogStatusService = $ordersLogStatusService;
        $this->sendHandler = $sendHandler;
    }

    /**
     * @param  \Illuminate\Support\Collection  $rooms
     * @param  array                           $response
     */
    public function handle(Collection $rooms, array $response): void
    {
        $allMessages = $this->chatMsgService->getNotReadMessagesByRoomIds($rooms->pluck('room_id')->all());

        $sentResponses = $this->autoresponderService->getSentMessagesByMessageIds(
            $allMessages->pluck('id')->all(),
            ChatAutoresponder::TYPE_NORMS
        );

        $list = $this->getListToSend($allMessages, $sentResponses, $response);

        if (!$list->count()) {
            return;
        }

        $this->sendByNorms($list);
    }

    /**
     * Получаем список сообщения для отправки
     *
     * @param  \Illuminate\Support\Collection  $allMessages
     * @param  \Illuminate\Support\Collection  $sentResponses
     * @param  array                           $response
     *
     * @return \Illuminate\Support\Collection
     */
    private function getListToSend(Collection $allMessages, Collection $sentResponses, array $response): Collection
    {
        $list = new Collection();
        $sentRooms = $sentResponses->pluck('room_id');
        $rooms = [];

        // @todo
        foreach ($allMessages->all() as $message) {
            $rooms[$message->room_id][$message->create] = $message->id;
        }

        foreach ($rooms as $roomId => $messages) {
            krsort($messages);
            $firstTime = array_key_last($messages);
            $firstId = last($messages);
            $firstTimeOut = $firstTime + $response['hours'] * 60 * 60;

            if ($firstTimeOut > time()) {
                continue;
            }

            if ($sentRooms->search($roomId, true) === false) {
                $list->push(
                    [
                        'room_id'   => $roomId,
                        'id'        => $firstId,
                        'time'      => $firstTime,
                        'messages'  => $response['message'],
                        'normative' => $response['normative'],
                    ]
                );
            } elseif (!empty($response['second'])) {
                $listSent = $sentResponses
                    ->where('room_id', $roomId)
                    ->where('type', ChatAutoresponder::TYPE_NORMS);

                if ($listSent->count() > 1) {
                    continue;
                }

                $firstSentMessageTime = $listSent->first()->created_at->timestamp;

                if ($firstSentMessageTime + $response['second']['hours'] * 60 * 60 > time()) {
                    continue;
                }

                $list->push(
                    [
                        'room_id'   => $roomId,
                        'id'        => $firstId,
                        'time'      => $firstTime,
                        'messages'  => $response['second']['message'],
                        'normative' => $response['normative'],
                    ]
                );
            }
        }

        return $list;
    }

    /**
     * Отправить сообщения по нормам
     *
     * @param  \Illuminate\Support\Collection  $list
     */
    private function sendByNorms(Collection $list): void
    {
        $logsRaw = $this->ordersLogService->getLogsByChatMessagesIds($list->pluck('id')->all());
        $logs = [];

        foreach ($logsRaw as $log) {
            $logs[$log->to_staff_id][$log->msgId] = $list->firstWhere('id', $log->msgId);
        }

        foreach ($logs as $staffId => $items) {
            $watchedIds = $this->ordersLogStatusService->getCurrentWatchedLogIds($staffId);
            $normsCount = $this->ordersLogService->getCurrentNormsCount($watchedIds->all());

            foreach ($items as $item) {
                if ($item['normative'] >= $normsCount) {
                    continue;
                }

                $this->sendHandler->handle(
                    $item['messages'],
                    $item['room_id'],
                    $item['id'],
                    ChatAutoresponder::TYPE_NORMS
                );
            }
        }
    }
}
