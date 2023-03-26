<?php

namespace App\Services\Antiplagiat\Model;
class DocumentId
{
    private $Id;
    private $ExternalId;

    public function __construct(object $docId)
    {
        $this->Id = $docId->Id;
        $this->ExternalId = $docId->External;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'Id'       => $this->Id,
            'External' => $this->ExternalId
        ];
    }

    public function toJson()
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->ExternalId;
    }
}
