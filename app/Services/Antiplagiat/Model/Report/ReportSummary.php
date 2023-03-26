<?php

namespace App\Services\Antiplagiat\Model\Report;

class ReportSummary
{
    private int       $reportNum;
    private \DateTime $readyTime;
    private float     $score;
    private string    $webId;
    private string    $readOnlyWebId;
    private string    $shortWebId;
    private Score     $detailedScore;
    private Score     $baseScore;
    private bool      $isSuspicious;
    private string    $summaryWebId;
    private ?string    $shortFraudWebId;

    public function __construct(object $summary)
    {
        $this->reportNum = $summary->ReportNum;
        $this->readyTime = new \DateTime($summary->ReadyTime);
        $this->score = $summary->Score;
        $this->webId = $summary->ReportWebId;
        $this->readOnlyWebId = $summary->ReadonlyReportWebId;
        $this->shortWebId = $summary->ShortReportWebId;
        $this->detailedScore = new Score($summary->DetailedScore);
        $this->baseScore = new Score($summary->BaseScore);
        $this->isSuspicious = $summary->IsSuspicious;
        $this->summaryWebId = $summary->SummaryReportWebId;
        $this->shortFraudWebId = $summary->ShortFraudReportWebId;

    }

    /**
     * @return Score
     */
    public function getDetailedScore(): Score
    {
        return $this->detailedScore;
    }

    /**
     * @return string
     */
    public function getWebId(): string
    {
        return $this->webId;
    }
}
