<?php

namespace App\Jobs\Antiplagiat;

use App\Jobs\Queue;
use App\Models\Files\Files;
use App\Services\Antiplagiat\CheckOriginalityService;
use App\Services\Antiplagiat\Exception\ApiCorpException;
use App\Services\Antiplagiat\Exception\ApiCorpUndefinedException;
use App\Services\Antiplagiat\Exception\DocumentIdException;
use App\Services\Antiplagiat\Exception\EmptyFileInfoException;
use App\Services\Antiplagiat\Exception\ImmediateException;
use App\Services\Antiplagiat\Exception\InvalidArgumentException;
use App\Services\Antiplagiat\Exception\OperationDenialException;
use App\Services\Antiplagiat\Exception\PermissionExcepted;
use App\Services\Antiplagiat\Exception\UserNotFoundException;
use App\Services\CrmApi\CrmApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use JsonException;
use Log;
use Psr\Log\LoggerInterface;
use Throwable;

class UploadFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DELAY_BEFORE_NEXT_UPLOAD_TRY = 15;
    public                  $tries = 5;
    private array           $data;
    private LoggerInterface $logger;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->data['file_id'])];
    }

    /**
     * @return void
     * @throws ApiCorpException
     * @throws ApiCorpUndefinedException
     * @throws DocumentIdException
     * @throws EmptyFileInfoException
     * @throws ImmediateException
     * @throws InvalidArgumentException
     * @throws OperationDenialException
     * @throws PermissionExcepted
     * @throws UserNotFoundException
     * @throws JsonException
     */
    public function handle()
    {
        $this->logger = Log::stack(['antiplagiat']);
        $file = Files::findOrFail($this->data['file_id']);
        $service = new CheckOriginalityService();
        try {
            $check = $service->prepare($file, $this->data['author_id'])->upload();
            CheckFileJob::dispatch($check)->onQueue(Queue::ANTIPLAGIAT);
        } catch (ApiCorpException $e) {
            $this->logger->error('Ошибка: ' . get_class($e) . ' - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            $this->release(self::DELAY_BEFORE_NEXT_UPLOAD_TRY);
        } catch (Throwable $e) {
            $this->logger->error('Ошибка: ' . get_class($e) . ' - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }

    }

    public function failed(Throwable $exception, CrmApiService $crmApiService)
    {
        $crmApiService->saveDebugLog('ERROR_Antiplagiat_Upload', json_encode(
            [
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString()
            ]
        ));
    }
}
