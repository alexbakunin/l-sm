<?php

namespace App\Services\ChatAutoresponder\Handlers;

use App\Services\ChatAutoresponder\Repositories\ChatAutoresponderRepository;
use App\Services\ChatMsg\ChatMsgService;

/**
 * Class ChatAutoresponderSendHandler
 * Отправка сообщения в чат и сохранения в таблице авто ответов
 *
 * @package App\Services\ChatAutoresponder\Handlers
 */
class ChatAutoresponderSendHandler
{
    /**
     * @var ChatAutoresponderRepository
     */
    private ChatAutoresponderRepository $autoresponderRepository;
    /**
     * @var ChatMsgService
     */
    private ChatMsgService $chatMsgService;

    /**
     * ChatAutoresponderSendHandler constructor.
     *
     * @param  ChatAutoresponderRepository  $autoresponderRepository
     * @param  ChatMsgService               $chatMsgService
     */
    public function __construct(ChatAutoresponderRepository $autoresponderRepository, ChatMsgService $chatMsgService)
    {
        $this->autoresponderRepository = $autoresponderRepository;
        $this->chatMsgService = $chatMsgService;
    }

    /**
     * @param  string  $message
     * @param  int     $roomId
     * @param  int     $messageId
     * @param  string  $type
     */
    public function handle(string $message, int $roomId, int $messageId, string $type): void
    {
        $responseMessageId = $this->chatMsgService->send($roomId, $message);

        if (!$this->autoresponderRepository->send($roomId, $messageId, $responseMessageId, $type)) {
            throw new \RuntimeException("Ошибка при добавление записи об автоответе");
        }
    }

}
