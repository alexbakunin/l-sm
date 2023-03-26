<?php

namespace App\Services\Antiplagiat\Model;

class CheckServicesList
{
    private ?array $services;

    public function __construct(object $list)
    {
        foreach ($list->CheckServiceInfo as $item) {
            $this->services[] = new CheckServiceInfo($item);
        }
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
