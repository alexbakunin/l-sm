<?php

namespace App\Services\ChatAutoresponder;

use App\Services\ChatAutoresponder\Exceptions\ChatAutoresponderTypeDisabled;
use App\Services\ChatAutoresponder\Handlers\ChatAutoresponderNormsSendHandler;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Collection;

/**
 * Class ChatAutoresponderNormsService
 *
 * @package App\Services\ChatAutoresponder
 */
class ChatAutoresponderNormsService
{
    /**
     * @var SettingsService
     */
    private SettingsService $settingsService;
    /**
     * @var ChatAutoresponderNormsSendHandler
     */
    private ChatAutoresponderNormsSendHandler $chatAutoresponderNormsSendHandler;

    /**
     * ChatAutoresponderNormsService constructor.
     *
     * @param  SettingsService                    $settingsService
     * @param  ChatAutoresponderNormsSendHandler  $chatAutoresponderNormsSendHandler
     */
    public function __construct(
        SettingsService $settingsService,
        ChatAutoresponderNormsSendHandler $chatAutoresponderNormsSendHandler
    ) {
        $this->settingsService = $settingsService;
        $this->chatAutoresponderNormsSendHandler = $chatAutoresponderNormsSendHandler;
    }

    /**
     * Получить параметры автоответа
     *
     * @return array
     * @throws ChatAutoresponderTypeDisabled
     */
    public function getResponseParams(): array
    {
        $settings = $this->settingsService->getNormsSettings();

        if ((int)($settings['active'] ?? 0) !== 1 || empty($settings['message'])
            || (empty($settings['value']) && empty($settings['hours']))
        ) {
            throw new ChatAutoresponderTypeDisabled();
        }

        $params = [
            'message'   => $settings['message'],
            'normative' => (int)($settings['value'] ?? 0),
            'hours'     => (int)($settings['hours'] ?? 0),
        ];

        $settings = $this->settingsService->getNorms2Settings();

        if ((int)($settings['active'] ?? 0) !== 1 || empty($settings['message']) || empty($settings['value'])) {
            return $params;
        }

        $params['second'] = [
            'message' => $settings['message'],
            'hours'   => (int)($settings['value'] ?? 0),
        ];

        return $params;
    }

    public function sendMessage(Collection $rooms, array $response)
    {
        $this->chatAutoresponderNormsSendHandler->handle($rooms, $response);
    }
}
