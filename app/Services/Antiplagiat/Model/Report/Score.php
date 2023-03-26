<?php

namespace App\Services\Antiplagiat\Model\Report;

class Score
{
    private float $plagiarism;
    private float $legal;
    private float $selfCite;
    private float $unknown;

    public function __construct(object $score)
    {
        $this->plagiarism = $score->Plagiarism;
        $this->legal = $score->Legal;
        $this->selfCite = $score->SelfCite;
        $this->unknown = $score->Unknown;
    }

    /**
     * @return float
     */
    public function getPlagiarism(): float
    {
        return $this->plagiarism;
    }

    /**
     * @return float
     */
    public function getLegal(): float
    {
        return $this->legal;
    }

    /**
     * @return float
     */
    public function getSelfCite(): float
    {
        return $this->selfCite;
    }

    /**
     * @return float
     */
    public function getUnknown(): float
    {
        return $this->unknown;
    }

    public function toArray()
    {
        return [
            'plagiarism' => $this->plagiarism,
            'legal'      => $this->legal,
            'self_cite'  => $this->selfCite,
            'unknown'    => $this->unknown
        ];
    }

}
