<?php

declare(strict_types=1);

namespace App\Services\CrmEmail;

use App\Services\CrmEmail\DTO\EmailDto;
use App\Services\CrmEmail\Repositories\CrmEmailSubscribeRepository;
use App\Services\CrmEmail\Repositories\CrmEmailTplsLogRepository;

class CrmEmailService
{
    /** @var int[] */
    private const NEED_CHECK_SUBSCRIBE_OFFICES = [1, 2];

    /**
     * @var \App\Services\CrmEmail\Repositories\CrmEmailTplsLogRepository
     */
    private CrmEmailTplsLogRepository $tplsLogRepository;

    /**
     * @var \App\Services\CrmEmail\Repositories\CrmEmailSubscribeRepository
     */
    private CrmEmailSubscribeRepository $subscribeRepository;

    /**
     * @param  \App\Services\CrmEmail\Repositories\CrmEmailTplsLogRepository    $tplsLogRepository
     * @param  \App\Services\CrmEmail\Repositories\CrmEmailSubscribeRepository  $subscribeRepository
     */
    public function __construct(
        CrmEmailTplsLogRepository   $tplsLogRepository,
        CrmEmailSubscribeRepository $subscribeRepository
    ) {
        $this->tplsLogRepository = $tplsLogRepository;
        $this->subscribeRepository = $subscribeRepository;
    }

    /**
     * @param  \App\Services\CrmEmail\DTO\EmailDto  $dto
     *
     * @return int
     */
    public function setLog(EmailDto $dto): int
    {
        return $this->tplsLogRepository->set($dto);
    }

    /**
     * @param  string  $email
     * @param  int     $officeId
     *
     * @return bool
     */
    public function checkSubscribe(string $email, int $officeId): bool
    {
        if (!in_array($officeId, self::NEED_CHECK_SUBSCRIBE_OFFICES, true)) {
            return true;
        }

        return $this->subscribeRepository->checkSubscribe($email, $officeId);
    }
}
