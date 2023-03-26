<?php

namespace App\Services\ChatAutoresponder\Handlers;

use App\Services\ChatAutoresponder\Exceptions\ChatAutoresponderNightNotFit;
use App\Services\ChatAutoresponder\Exceptions\ChatAutoresponderTypeDisabled;
use App\Services\Settings\SettingsService;
use JetBrains\PhpStorm\Pure;

/**
 * Class ChatAutoresponderNightGetMessageHandler
 * Метод проверяет нужно ли отправлять сообщение об не работе офиса и если нужно возвращает его текст
 *
 * @package App\Services\ChatAutoresponder\Handlers
 */
class ChatAutoresponderNightGetMessageHandler
{
    /**
     * @var SettingsService
     */
    private SettingsService $settingsService;

    /**
     * ChatAutoresponderNightSendHandler constructor.
     *
     * @param  SettingsService  $settingsService
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @return string
     * @throws ChatAutoresponderNightNotFit
     * @throws ChatAutoresponderTypeDisabled
     */
    public function handle(): string
    {
        $settings = $this->settingsService->getNightSettings();

        if ((int)($settings['active'] ?? 0) !== 1 || empty($settings['message'])) {
            throw new ChatAutoresponderTypeDisabled();
        }

        if (!$this->checkInPeriod($settings)) {
            throw new ChatAutoresponderNightNotFit();
        }

        return $settings['message'];
    }

    /**
     * @param  array  $settings
     *
     * @return bool
     */
    #[Pure] private function checkInPeriod(array $settings): bool
    {
        $from = $settings['time_from_' . (date('N') - 1)] ?? null;
        $to = $settings['time_to_' . (date('N') - 1)] ?? null;

        if (!$from || !$to) {
            return false;
        }

        $date = date('H:i');

        return $date < $from || $date > $to;
    }

}
