<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Jobs\EmailSender;
use App\Jobs\Queue;
use App\Services\CrmEmail\DTO\EmailDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Email extends Controller
{

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $log = Log::stack(['email']);
        $list = $request->post('list');

        if (!$list || !is_array($list)) {
            abort(404);
        }

        $errors = [];

        foreach ($list as $item) {
            $log->debug(
                sprintf(
                    "send: Получены данные: from %s | to %s | subject %s",
                    $item['from'] ?? '',
                    $item['to'] ?? '',
                    $item['subject'] ?? '',
                )
            );

            $dto = EmailDto::makeFromArray($item);

            if (is_null($dto)) {
                $log->error(
                    sprintf(
                        "send: Ошибка данных: from %s | to %s | subject %s",
                        $item['from'] ?? '',
                        $item['to'] ?? '',
                        $item['subject'] ?? '',
                    )
                );
                $errors[] = $item;
                continue;
            }

            EmailSender::dispatch($dto)
                ->onQueue(empty($item[Queue::AUTHOR_CRON_SET]) ? Queue::EMAILS : Queue::AUTHOR_CRON_SET);

            $log->debug(
                sprintf(
                    "send: Данные добавлены в очередь: from %s | to %s | subject %s",
                    $dto->from,
                    $dto->to,
                    $dto->subject
                )
            );
        }

        return response()->json(['status' => !$errors, 'errors' => $errors]);
    }
}
