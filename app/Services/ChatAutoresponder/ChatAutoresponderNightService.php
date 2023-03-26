<?php

namespace App\Services\ChatAutoresponder;

use App\Services\ChatAutoresponder\Handlers\ChatAutoresponderNightGetMessageHandler;
use App\Services\ChatAutoresponder\Handlers\ChatAutoresponderNightSendHandler;
use Illuminate\Support\Collection;

/**
 * Class ChatAutoresponderNightService
 *
 * @package App\Services\ChatAutoresponder
 */
class ChatAutoresponderNightService
{
    /**
     * @var ChatAutoresponderNightGetMessageHandler
     */
    private ChatAutoresponderNightGetMessageHandler $getMessageHandler;
    /**
     * @var ChatAutoresponderNightSendHandler
     */
    private ChatAutoresponderNightSendHandler $nightSendHandler;

    /**
     * ChatAutoresponderNightService constructor.
     *
     * @param  ChatAutoresponderNightGetMessageHandler  $getMessageHandler
     * @param  ChatAutoresponderNightSendHandler        $nightSendHandler
     */
    public function __construct(
        ChatAutoresponderNightGetMessageHandler $getMessageHandler,
        ChatAutoresponderNightSendHandler $nightSendHandler
    ) {
        $this->getMessageHandler = $getMessageHandler;
        $this->nightSendHandler = $nightSendHandler;
    }

    /**
     * Получить сообщение для отправки в ночном режиме
     *
     * @return string
     * @throws Exceptions\ChatAutoresponderNightNotFit
     * @throws Exceptions\ChatAutoresponderTypeDisabled
     */
    public function getMessage(): string
    {
        return $this->getMessageHandler->handle();
    }

    /**
     * Отправка сообщений о не рабочем времении
     *
     * @param  Collection  $rooms
     * @param  string      $response
     */
    public function sendMessage(Collection $rooms, string $response): void
    {
        $this->nightSendHandler->handle($rooms, $response);
    }
}
