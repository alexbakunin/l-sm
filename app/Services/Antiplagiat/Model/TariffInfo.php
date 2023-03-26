<?php

namespace App\Services\Antiplagiat\Model;

class TariffInfo
{
    private string            $name;
    private \DateTime         $subscriptionDate;
    private \DateTime         $expirationDate;
    private CheckServicesList $services;
    private ?int              $totalChecksCount;
    private ?int              $remainedChecksCount;

    public function __construct(object $tariff)
    {
        $this->name = $tariff->Name;
        $this->subscriptionDate = new \DateTime($tariff->SubscriptionDate);
        $this->expirationDate = new \DateTime($tariff->SubscriptionDate);
        $this->services = new CheckServicesList($tariff->CheckServices);
        $this->totalChecksCount = $tariff->TotalChecksCount;
        $this->remainedChecksCount = $tariff->RemainedChecksCount;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getSubscriptionDate(): \DateTime
    {
        return $this->subscriptionDate;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate(): \DateTime
    {
        return $this->expirationDate;
    }

    /**
     * @return CheckServicesList
     */
    public function getServices(): CheckServicesList
    {
        return $this->services;
    }

    /**
     * @return int|null
     */
    public function getTotalChecksCount(): ?int
    {
        return $this->totalChecksCount;
    }

    /**
     * @return int|null
     */
    public function getRemainedChecksCount(): ?int
    {
        return $this->remainedChecksCount;
    }

}
