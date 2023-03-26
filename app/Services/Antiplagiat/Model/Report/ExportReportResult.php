<?php

namespace App\Services\Antiplagiat\Model\Report;

class ExportReportResult
{
    public ExportReportStatus $status;
    private ?string           $downloadLink;
    private int               $reportNum;
    private ?int              $estimatedWaitTime;

    public function __construct(object $export)
    {
        $this->status = new ExportReportStatus($export->Status);
        $this->downloadLink = $export->DownloadLink;
        $this->reportNum = $export->ReportNum;
        $this->estimatedWaitTime = $export->EstimatedWaitTime;
    }

    /**
     * @return string
     */
    public function getDownloadLink(): string
    {
        return $this->downloadLink;
    }

    /**
     * @return int|null
     */
    public function getEstimatedWaitTime(): ?int
    {
        return $this->estimatedWaitTime;
    }

    /**
     * @return int
     */
    public function getReportNum(): int
    {
        return $this->reportNum;
    }

    /**
     * @return ExportReportStatus
     */
    public function getStatus(): ExportReportStatus
    {
        return $this->status;
    }
}
