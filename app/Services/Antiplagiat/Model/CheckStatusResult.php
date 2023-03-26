<?php

namespace App\Services\Antiplagiat\Model;

use App\Services\Antiplagiat\Model\Report\ReportStatus;
use App\Services\Antiplagiat\Model\Report\ReportSummary;

class CheckStatusResult
{
    private DocumentId     $docId;
    public ReportStatus    $status;
    private ?string        $failDetails;
    private ?ReportSummary $summary;
    private ?int           $estimatedTime;

    public function __construct(object $result)
    {
        $this->docId = new DocumentId($result->DocId);
        $this->status = new ReportStatus($result->Status);
        $this->failDetails = $result->FailDetails;
        $this->summary = $result->Summary ? new ReportSummary($result->Summary) : NULL;
        $this->estimatedTime = $result->EstimatedWaitTime;
    }

    /**
     * @return ReportStatus
     */
    public function getStatus(): ReportStatus
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getFailDetails(): ?string
    {
        return $this->failDetails;
    }

    /**
     * @return ReportSummary|null
     */
    public function getSummary(): ?ReportSummary
    {
        return $this->summary;
    }

    /**
     * @return int
     */
    public function getEstimatedTime(): int
    {
        return $this->estimatedTime;
    }
}
