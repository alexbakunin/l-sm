<?php

namespace App\Services\Antiplagiat\Client;

use App\Services\Antiplagiat\Exception\ApiCorpException;
use App\Services\Antiplagiat\Exception\ApiCorpUndefinedException;
use App\Services\Antiplagiat\Exception\DocumentIdException;
use App\Services\Antiplagiat\Exception\ImmediateException;
use App\Services\Antiplagiat\Exception\InvalidArgumentException;
use App\Services\Antiplagiat\Exception\OperationDenialException;
use App\Services\Antiplagiat\Exception\PermissionExcepted;
use App\Services\Antiplagiat\Exception\UserNotFoundException;
use App\Services\Antiplagiat\Model\DocumentId;
use PhpParser\Comment\Doc;
use SoapClient;

class AntiplagiatClient
{
    private SoapClient $client;
    private string     $connectionUrl;
    private object     $docId;

    /**
     * @throws \SoapFault
     */
    public function __construct()
    {
        $this->connectionUrl = sprintf('https://%s/apiCorp/%s?singleWsdl', config('antiplagiat.apicorp_adderss'), config('antiplagiat.company_name'));


        $this->client = new SoapClient($this->connectionUrl,
            [
                'trace'        => (int)config('antiplagiat.debug'),
                'login'        => config('antiplagiat.login'),
                'password'     => config('antiplagiat.password'),
                'soap_version' => SOAP_1_1,
                'features'     => SOAP_SINGLE_ELEMENT_ARRAYS,
                'exceptions'   => true
            ]);
    }

    /**
     * @return SoapClient
     */
    public function getClient(): SoapClient
    {
        return $this->client;
    }


    /**
     * @param array $data
     * @return $this|null
     * @throws ApiCorpException
     * @throws ImmediateException
     * @throws InvalidArgumentException
     * @throws OperationDenialException
     * @throws PermissionExcepted
     * @throws UserNotFoundException
     * @throws ApiCorpUndefinedException
     * @throws \JsonException
     */
    public function uploadDocument(array $data): ?AntiplagiatClient
    {
        $dataWithOutContent = $data;
        unset($dataWithOutContent['Data']);
        try {
            $upload = $this->client->UploadDocument(['data' => $data]);
            $this->docId = $upload->UploadDocumentResult->Uploaded[0]->Id;
            return $this;
        } catch (\SoapFault $e) {
            // parse soap result errors and exceptions
            foreach ($e->detail as $exception => $msg) {
                $this->throwException($exception, $msg?->Message);
            }
            return NULL;
        }
    }

    /**
     * @return bool
     * @throws ApiCorpException
     * @throws ApiCorpUndefinedException
     * @throws DocumentIdException
     * @throws ImmediateException
     * @throws InvalidArgumentException
     * @throws PermissionExcepted
     * @throws \JsonException
     */
    public function send(array $params): array|bool
    {
        try {
            $sendParams = array_merge(['docId' => $this->docId], $params);
            $check = $this->client->CheckDocument($sendParams);
            return ['docId' => $this->docId];
        } catch (\SoapFault $e) {
            // parse soap result errors and exceptions
            foreach ($e->detail as $exception => $msg) {
                $this->throwException($exception, $msg?->Message);
            }
            return false;
        }
    }


    /**
     * @param DocumentId $docId
     * @return object|false
     * @throws ApiCorpException
     * @throws DocumentIdException
     * @throws InvalidArgumentException
     */
    public function getStatus(DocumentId $docId)
    {
        try {
            return $this->client->GetCheckStatus(['docId' => $docId]);
        } catch (\SoapFault $e) {
            // parse soap result errors and exceptions
            foreach ($e->detail as $exception => $msg) {
                $this->throwException($exception, $msg?->Message);
            }
            return false;
        }
    }


    /**
     * @return object|false
     * @throws ApiCorpException
     */
    public function getTariffInfo()
    {
        try {
            return $this->client->GetTariffInfo();
        } catch (\SoapFault $e) {
            // parse soap result errors and exceptions
            foreach ($e->detail as $exception => $msg) {
                $this->throwException($exception, $msg?->Message);
            }
            return false;
        }
    }


    /**
     * @param DocumentId $docId
     * @param array $params
     * @throws ApiCorpException
     * @throws ApiCorpUndefinedException
     * @throws DocumentIdException
     * @throws ImmediateException
     * @throws InvalidArgumentException
     * @throws OperationDenialException
     * @throws PermissionExcepted
     */
    public function exportReportToPdf(DocumentId $docId, array $params = [])
    {
        try {
            return $this->client->ExportReportToPdf(['docId' => $docId]);
        } catch (\SoapFault $e) {
            // parse soap result errors and exceptions
            foreach ($e->detail as $exception => $msg) {
                $this->throwException($exception, $msg?->Message);
            }
            return false;
        }
    }

    private function throwException(string $e, string $msg)
    {
        switch ($e) {
            case 'ImmediateException':
                throw new ImmediateException($msg);
            case 'InvalidArgumentException':
                throw new InvalidArgumentException($msg);
            case 'UserNotFoundException':
                throw new UserNotFoundException($msg);
            case 'PermissionException':
                throw new PermissionExcepted($msg);
            case 'OperationDenialException':
                throw new OperationDenialException($msg);
            case 'ApiCorpException':
                throw new ApiCorpException($msg);
            case 'DocumentIdException':
                throw new DocumentIdException($msg);
            default:
                throw new ApiCorpUndefinedException($e . ' - ' . $msg);
        }
    }


}
