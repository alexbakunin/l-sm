<?php

namespace App\Services\Antiplagiat\Model\Report;

use App\Services\Antiplagiat\Exception\UndefinedResultStatus;

class ExportReportStatus
{
    private string $status;
    private const STATUS_LIST = [
        'InProgress', 'Ready', 'Failed'
    ];
    public const STATUS_FAILED = 'Failed';
    public const STATUS_READY = 'Ready';
    public const STATUS_IN_PROGRESS = 'InProgress';

    /**
     * @param string $status
     * @throws UndefinedResultStatus
     */
    public function __construct(string $status)
    {
        if (!in_array($status, self::STATUS_LIST)) {
            throw new UndefinedResultStatus('Неизвестный статус результата проверки');
        }
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    /**
     * @return bool
     */
    public function notReady(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS && $this->status !== self::STATUS_FAILED;
    }


    /**
     * @return string
     */
    public function readable(): string
    {
        return $this->status;
    }

}
