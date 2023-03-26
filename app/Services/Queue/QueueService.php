<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Jobs\Queue as QueueName;
use App\Services\Queue\Repositories\AuthorsCronSetRepository;
use Illuminate\Support\Facades\Queue as QueueFacade;

class QueueService
{
    private const QUEUE_CRM_DB = QueueName::AUTHOR_CRON_SET . '_crm_db';
    private static array $names = [
        QueueName::AUTHOR_CRON_SET => 'Предложения авторам (e-mail)',
        QueueName::ANTIPLAGIAT     => 'Проверка оригинальности',
        QueueName::EMAILS          => 'Письма',
        QueueName::HIGH            => 'Высокий приоритет',
        QueueName::LOW             => 'Низкий приоритет',
        QueueName::SMS             => 'Отправка sms',
        self::QUEUE_CRM_DB         => 'Предложения авторам (CRM)',
    ];
    /**
     * @var \App\Services\Queue\Repositories\AuthorsCronSetRepository
     */
    private AuthorsCronSetRepository $authorsCronSetRepository;

    /**
     * @param \App\Services\Queue\Repositories\AuthorsCronSetRepository $authorsCronSetRepository
     */
    public function __construct(AuthorsCronSetRepository $authorsCronSetRepository)
    {
        $this->authorsCronSetRepository = $authorsCronSetRepository;
    }

    /**
     * @return array
     */
    public function getSize(): array
    {
        return [
            self::QUEUE_CRM_DB         => [
                'count' => $this->authorsCronSetRepository->getCount(),
                'name'  => self::$names[self::QUEUE_CRM_DB] ?? self::QUEUE_CRM_DB,
            ],
            QueueName::AUTHOR_CRON_SET => [
                'count' => QueueFacade::size(QueueName::AUTHOR_CRON_SET),
                'name'  => self::$names[QueueName::AUTHOR_CRON_SET] ?? QueueName::AUTHOR_CRON_SET,
            ],
            QueueName::EMAILS          => [
                'count' => QueueFacade::size(QueueName::EMAILS),
                'name'  => self::$names[QueueName::EMAILS] ?? QueueName::EMAILS,
            ],
            QueueName::HIGH            => [
                'count' => QueueFacade::size(QueueName::HIGH),
                'name'  => self::$names[QueueName::HIGH] ?? QueueName::HIGH,
            ],
            QueueName::LOW             => [
                'count' => QueueFacade::size(QueueName::LOW),
                'name'  => self::$names[QueueName::LOW] ?? QueueName::LOW,
            ],
            QueueName::SMS             => [
                'count' => QueueFacade::size(QueueName::SMS),
                'name'  => self::$names[QueueName::SMS] ?? QueueName::SMS,
            ],
            QueueName::ANTIPLAGIAT             => [
                'count' => QueueFacade::size(QueueName::ANTIPLAGIAT),
                'name'  => self::$names[QueueName::ANTIPLAGIAT] ?? QueueName::ANTIPLAGIAT,
            ],
        ];
    }
}
