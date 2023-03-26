<?php

declare(strict_types=1);

namespace App\Services\CrmEmail\Repositories;

use App\Services\CrmEmail\DTO\EmailDto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CrmEmailTplsLogRepository
{
    /** @var string */
    private const TABLE = 'tpls_log';

    /**
     * @param  \App\Services\CrmEmail\DTO\EmailDto  $dto
     *
     * @return int
     */
    public function set(EmailDto $dto): int
    {
        return $this->getBuilder()->insertGetId(
            [
                'user_id' => $dto->senderId,
                'date' => time(),
                'type' => 'email',
                'tpls_id' => $dto->tplId,
                "to" => $dto->to,
                'warning' => $dto->warning,
                'unisender_id' => $dto->senderSystemId,
                'smtp' => $dto->smtp,
                'sendType' => $dto->sendType,
                'comment' => $dto->comment ?: $dto->subject,
            ]
        );
    }

    /**
     * @return Builder
     */
    private function getBuilder(): Builder
    {
        return DB::connection('crm')->table(self::TABLE);
    }
}
