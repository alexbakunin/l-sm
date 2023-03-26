<?php

namespace App\Console\Commands;

use App\Services\ChatAutoresponder\ChatAutoresponderNightService;
use App\Services\ChatAutoresponder\ChatAutoresponderNormsService;
use App\Services\ChatAutoresponder\Exceptions\ChatAutoresponderNightNotFit;
use App\Services\ChatAutoresponder\Exceptions\ChatAutoresponderTypeDisabled;
use App\Services\ChatMsg\ChatMsgService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class ChatAutoresponder
 *
 * @package App\Console\Commands
 */
class ChatAutoresponder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:autoresponder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Комманда для отправки автоответов в чат';
    /**
     * @var ChatAutoresponderNightService|null
     */
    private ?ChatAutoresponderNightService $chatAutoresponderNight;
    /**
     * @var ChatMsgService|null
     */
    private ?ChatMsgService $chatMsgService;
    /**
     * @var \App\Services\ChatAutoresponder\ChatAutoresponderNormsService|null
     */
    private ?ChatAutoresponderNormsService $chatAutoresponderNorms;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param  ChatAutoresponderNightService                                  $chatAutoresponderNight
     * @param  ChatMsgService                                                 $chatMsgService
     * @param  \App\Services\ChatAutoresponder\ChatAutoresponderNormsService  $chatAutoresponderNorms
     *
     * @return int
     */
    public function handle(
        ChatAutoresponderNightService $chatAutoresponderNight,
        ChatMsgService $chatMsgService,
        ChatAutoresponderNormsService $chatAutoresponderNorms
    ) {
        $this->chatAutoresponderNight = $chatAutoresponderNight;
        $this->chatMsgService = $chatMsgService;
        $this->chatAutoresponderNorms = $chatAutoresponderNorms;

        if ($this->runNightShift()) {
            return 0;
        }

        $this->runNorms();

        return 0;
    }

    /**
     * Ночная смена
     * @return bool
     */
    private function runNightShift(): bool
    {
        $this->getOutput()->info('Проверяем активна ли ночная смена');

        try {
            $response = $this->chatAutoresponderNight->getMessage();
            $rooms = $this->chatMsgService->getNotReadRoomsByTime(new \Carbon\Carbon("-1 minute"));

            $this->getOutput()->info(sprintf("Записей для отправки: %d", $rooms->count()));

            if (!$rooms->count()) {
                return true;
            }

            $this->chatAutoresponderNight->sendMessage($rooms, $response);

            $this->getOutput()->success('Сообщения отправлены');

            return true;
        } catch (ChatAutoresponderTypeDisabled $e) {
            $this->getOutput()->warning('Ночная смена не активна');
            return true;
        } catch (ChatAutoresponderNightNotFit $e) {
            $this->getOutput()->warning('Ночная смена не активна');
            return false;
        }
    }

    /**
     * Нормативы
     * @return bool
     */
    private function runNorms(): bool
    {
        $this->getOutput()->info('Проверяем нормативы');

        try {
            $response = $this->chatAutoresponderNorms->getResponseParams();
            $rooms = $this->chatMsgService->getNotReadRoomsByTime(new \Carbon\Carbon("-5 minute"));

            $this->getOutput()->info(sprintf("Записей для оработки: %d", $rooms->count()));

            if (!$rooms->count()) {
                return true;
            }

            $this->chatAutoresponderNorms->sendMessage($rooms, $response);

            $this->getOutput()->success('Сообщения отправлены');

            return true;
        } catch (ChatAutoresponderNightNotFit | ChatAutoresponderTypeDisabled $e) {
            $this->getOutput()->warning('Ночная смена не активна');
            return false;
        }
    }
}
