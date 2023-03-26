<?php

namespace App\Services\Antiplagiat\Model;

class CheckServiceInfo
{
    public string $code;
    public ?string $description;

    public function __construct($service)
    {
        $this->code = $service->Code;
        $this->description = $service->Description;
    }


}
