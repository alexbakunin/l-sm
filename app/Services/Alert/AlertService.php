<?php

declare(strict_types=1);

namespace App\Services\Alert;

use App\Services\Alert\Notifications\DefaultNotification;
use App\Services\Alert\Repositories\StaffRepository;
use Illuminate\Support\Facades\Notification;

class AlertService
{
    /**
     * @var \App\Services\Alert\Repositories\StaffRepository
     */
    private StaffRepository $staffRepository;

    public function __construct(StaffRepository $staffRepository)
    {
        $this->staffRepository = $staffRepository;
    }

    /**
     * Отправить сообщение администраторам
     *
     * @param  string  $message
     */
    public function sendAdminMessage(string $message): void
    {
        $users = [];
        foreach ($this->staffRepository->getAdminTelegramIds() as $staff) {
            $user = new \stdClass();
            $user->telegram_user_id = $staff->telegram_id;
            $user->content = $message;
            $users[] = $user;
        }
        Notification::send($users, new DefaultNotification());
    }
}
