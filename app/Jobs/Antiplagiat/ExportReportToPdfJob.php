<?php

namespace App\Jobs\Antiplagiat;

use App\Models\Files\FileCheck;
use App\Services\Antiplagiat\CheckOriginalityService;
use App\Services\Antiplagiat\Exception\ApiCorpException;
use App\Services\Antiplagiat\Model\DocumentId;
use App\Services\Antiplagiat\Model\Report\ExportReportStatus;
use App\Services\CrmApi\CrmApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class ExportReportToPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DELAY_BEFORE_NEXT_EXPORT_TRY = 20;
    public             $tries = 15;
    private DocumentId $documentId;

    public function __construct(DocumentId $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(CrmApiService $crmApiService)
    {
        $this->logger = Log::stack(['antiplagiat']);

        try {
            $service = new CheckOriginalityService();
            $info = $service->getPdfReport($this->documentId);
            $fileCheck = FileCheck::where('external_id->Id', $this->documentId->getId())->first();
            $fileCheck->update(['job_status' => NULL]);
            $fileCheck->update(['job_id' => NULL]);
            switch ($info->status->readable()) {
                case ExportReportStatus::STATUS_IN_PROGRESS:
                    $this->release($info->getEstimatedWaitTime() * 0.1 + self::DELAY_BEFORE_NEXT_EXPORT_TRY);
                    break;
                case ExportReportStatus::STATUS_READY:
                    $fileCheck->update(['report_link' => $service->getUrl() . $info->getDownloadLink()]);
                    $crmApiService->sendAntiplagiatCheckReport($fileCheck->id);
                    break;

            }
        } catch (ApiCorpException $e) {
            $this->logger->error('Ошибка: ' . get_class($e) . ' - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            $this->release(self::DELAY_BEFORE_NEXT_EXPORT_TRY);
        } catch (Throwable $e) {
            $this->logger->error('Ошибка: ' . get_class($e) . ' - ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            $this->delete();
        }
    }

    public function failed(Throwable $exception, CrmApiService $crmApiService)
    {
        $crmApiService->saveDebugLog('ERROR_Antiplagiat_ExportReport', json_encode(['code'    => $exception->getCode(),
                                                                              'file'    => $exception->getFile(),
                                                                              'message' => $exception->getMessage(),
                                                                              'trace'   => $exception->getTraceAsString()]
        ));
    }
}
