<?php

namespace App\Jobs\Antiplagiat;

use App\Models\Files\FileCheck;
use App\Services\Antiplagiat\CheckOriginalityService;
use App\Services\Antiplagiat\Exception\ApiCorpException;
use App\Services\Antiplagiat\Model\DocumentId;
use App\Services\Antiplagiat\Model\Report\ReportStatus;
use App\Services\CrmApi\CrmApiService;
use App\Services\CrmApi\Requests\CrmApiRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class CheckFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DELAY_BEFORE_NEXT_UPLOAD_TRY = 10;
    private const DELAY_BEFORE_NEXT_CHECK_STATUS_TRY = 30;
    public             $tries = 20;
    private DocumentId $documentId;
    private array      $data;
    private FileCheck  $fileCheck;

    public function __construct(DocumentId $documentId)
    {
        $this->documentId = $documentId;
        $this->fileCheck = FileCheck::where('external_id->Id', $this->documentId->getId())->first();
    }

    public function handle(CrmApiService $crmApiService)
    {
        $this->logger = Log::stack(['antiplagiat']);

        try {
            $service = new CheckOriginalityService();
            $info = $service->status($this->documentId);

            $fileCheckUpdate = ['status' => $info->status->readable()];
            $result = [];
            switch ($info->status->readable()) {
                case ReportStatus::STATUS_IN_PROGRESS:
                    $this->release($info->getEstimatedTime() * 0.1 + self::DELAY_BEFORE_NEXT_CHECK_STATUS_TRY);
                    break;
                case ReportStatus::STATUS_READY:
                    $result = [
                        'summary' => $info->getSummary()->getDetailedScore()->toArray(),
                        'url'     => $service->getUrl() . $info->getSummary()->getWebId()
                    ];
                    $crmApiService->confirmAntiplagiatCheck($this->fileCheck->id);
                    ExportReportToPdfJob::dispatch($this->documentId)->onQueue('antiplagiat');
                    break;
                case ReportStatus::STATUS_FAILED:
                    $result = [
                        'error' => $info->getFailDetails(),
                    ];
                    break;
            }
        } catch (ApiCorpException $e) {
            $this->logger->error('Ошибка: ' . get_class($e) . ' - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            $result = [
                'error' => 'Проблемы с доступом API Антиплагиат',
            ];
            $this->release(self::DELAY_BEFORE_NEXT_UPLOAD_TRY);
        } catch (Throwable $e) {
            $this->logger->error('Ошибка: ' . get_class($e) . ' - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            $result = [
                'error' => get_class($e) . ' - ' . $e->getMessage(),
            ];
            $this->delete();
        }
        $fileCheckUpdate['job_id'] = $this->job->uuid();
        $fileCheckUpdate['result'] = json_encode($result);
        $this->fileCheck->update($fileCheckUpdate);
    }

    public function failed(Throwable $exception)
    {
        $crmApiService = new CrmApiService(new CrmApiRequest());
        Log::stack(['antiplagiat'])->error('failed job');
        $this->fileCheck->update(['job_status' => 'failed']);
        $crmApiService->saveDebugLog('ERROR_Antiplagiat_CheckFile', json_encode(['code'    => $exception->getCode(),
                                                                           'file'    => $exception->getFile(),
                                                                           'message' => $exception->getMessage(),
                                                                           'trace'   => $exception->getTraceAsString()]
        ));
        // Send user notification of failure, etc...
    }
}
