<?php

namespace App\Jobs;

use App\Mail\DefaultCrm;
use App\Services\CrmEmail\CrmEmailService;
use App\Services\CrmEmail\DTO\EmailDto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mail;

class EmailSender implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    /**
     * @var \App\Services\CrmEmail\DTO\EmailDto
     */
    private EmailDto $dto;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EmailDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CrmEmailService $emailService)
    {
        $log = Log::stack(['email']);
        $id = uniqid('ss-', true);
        $this->dto->senderSystemId = $id;

        $log->debug(
            sprintf(
                "send: Обработка записи в очереди: id %s | from %s | to %s | subject %s",
                $this->dto->senderSystemId,
                $this->dto->from,
                $this->dto->to,
                $this->dto->subject
            )
        );

        $this->dto->sendType = Mail::getDefaultDriver();

        try {
            if (!$emailService->checkSubscribe($this->dto->to, $this->dto->officeId)) {
                $this->dto->warning = 'Клиент отписан от рассылок';
                $emailService->setLog($this->dto);
                $this->release();
                return;
            }

            Mail::to($this->dto->to)->send(
                new DefaultCrm(
                    $this->dto->from,
                    $this->dto->fromName,
                    $this->dto->subject,
                    $this->dto->content,
                    $this->dto->plain
                )
            );
        } catch (\Throwable $e) {
            $log->error(
                sprintf(
                    "send: Ошибка: " . $e->getMessage() . " | data: id: %s | from %s | to %s | subject %s",
                    $this->dto->senderSystemId,
                    $this->dto->from,
                    $this->dto->to,
                    $this->dto->subject
                )
            );

            $this->dto->warning = $e->getMessage();

            $this->fail();
        }

        $emailService->setLog($this->dto);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [1, 5, 10, 30, 50];
    }
}
