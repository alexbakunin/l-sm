<?php

namespace App\Services\Antiplagiat;

use App\Models\Files\FileCheck;
use App\Models\Files\Files;
use App\Models\Order;
use App\Services\Antiplagiat\Client\AntiplagiatClient;
use App\Services\Antiplagiat\Exception\ApiCorpException;
use App\Services\Antiplagiat\Exception\ApiCorpUndefinedException;
use App\Services\Antiplagiat\Exception\DocumentIdException;
use App\Services\Antiplagiat\Exception\EmptyFileInfoException;
use App\Services\Antiplagiat\Exception\ImmediateException;
use App\Services\Antiplagiat\Exception\InvalidArgumentException;
use App\Services\Antiplagiat\Exception\OperationDenialException;
use App\Services\Antiplagiat\Exception\PermissionExcepted;
use App\Services\Antiplagiat\Exception\UserNotFoundException;
use App\Services\Antiplagiat\Model\CheckStatusResult;
use App\Services\Antiplagiat\Model\DocumentId;
use App\Services\Antiplagiat\Model\Report\ExportReportResult;
use App\Services\Antiplagiat\Model\TariffInfo;
use App\Services\ClientOrder\Repositories\ClientOrderRepository;
use Illuminate\Support\Facades\Log;

class CheckOriginalityService
{
    private AntiplagiatClient        $anptiplagiat;
    private string                   $url;
    private int                      $author_id;
    private array                    $fileInfo;
    private Files                    $file;
    private DocumentId               $docId;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct()
    {
        $this->url = config('antiplagiat.url');
        $this->anptiplagiat = new AntiplagiatClient();
        $this->logger = Log::stack(['antiplagiat']);

    }

    public function prepare(Files $file, int $author_id)
    {
        $this->author_id = $author_id;
        $this->file = $file;
        $this->order = Order::findOrFail($this->file->order_id);
        $this->fileInfo = [
            'Data'           => file_get_contents($this->file->url),
            'FileName'       => $this->file->name,
            'FileType'       => '.' . $this->file->ext,
            'ExternalUserID' => uniqid('apiap-', true)
        ];
        return $this;
    }

    /**
     * @return DocumentId
     * @throws EmptyFileInfoException
     * @throws ApiCorpException
     * @throws ApiCorpUndefinedException
     * @throws DocumentIdException
     * @throws ImmediateException
     * @throws InvalidArgumentException
     * @throws OperationDenialException
     * @throws PermissionExcepted
     * @throws UserNotFoundException
     * @throws \JsonException
     */
    public function upload(): DocumentId
    {
        if ($this->isEmptyFileInfo()) {
            throw new EmptyFileInfoException('Не заполнена информация о файле');
        }
        $checkParameters = [];
//        if ($this->order->originality == 3) {
//            $checkParameters = ['checkServicesList' => config('antiplagiat.full_check')];
//        }
        $result = $this->anptiplagiat->uploadDocument($this->fileInfo)?->send($checkParameters);
        $docId = new DocumentId($result['docId']);
        FileCheck::create([
            'order_id'    => $this->file->order_id,
            'file_id'     => $this->file->id,
            'author_id'   => $this->author_id,
            'status'      => 'new',
            'external_id' => $docId->toJson()
        ]);
        return $docId;

    }


    /**
     * @param DocumentId $docId
     * @return CheckStatusResult
     * @throws ApiCorpException
     * @throws DocumentIdException
     * @throws InvalidArgumentException
     */
    public function status(DocumentId $docId): CheckStatusResult
    {
        $checkStatus = new CheckStatusResult($this->anptiplagiat->getStatus($docId)->GetCheckStatusResult);
        return $checkStatus;

    }

    public function getTariffInfo()
    {
        return new TariffInfo($this->anptiplagiat->getTariffInfo()->GetTariffInfoResult);
    }

    /**
     * @param DocumentId $docId
     * @return ExportReportResult
     * @throws ApiCorpException
     * @throws DocumentIdException
     * @throws InvalidArgumentException
     * @throws PermissionExcepted
     */
    public function getPdfReport(DocumentId $docId): ExportReportResult
    {
        $result = $this->anptiplagiat->exportReportToPdf($docId)->ExportReportToPdfResult;
        return  new ExportReportResult($result);

    }

    public function simple_check($fileContent, $fileName, $EXT, $userID)
    {

        $data = $this->get_doc_data($fileContent, $fileName, $EXT, $userID);
        $uploadResult = $this->client->UploadDocument(array("data" => $data));
        $id = $uploadResult->UploadDocumentResult->Uploaded[0]->Id;
        $this->client->CheckDocument(array("docId" => $id));
        $status = $this->client->GetCheckStatus(array("docId" => $id));

        while ($status->GetCheckStatusResult->Status === "InProgress") {
            sleep($status->GetCheckStatusResult->EstimatedWaitTime * 0.1);
            $status = $this->client->GetCheckStatus(array("docId" => $id));
        }

        if ($status->GetCheckStatusResult->Status === "Failed") {
            echo("При проверке документа произошла ошибка:" + $status->GetCheckStatusResult->FailDetails);
            return;
        }

        // Получить краткий отчет
        $report = $this->client->GetReportView(array("docId" => $id));

        foreach ($report->GetReportViewResult->CheckServiceResults as $checkService) {
            // Информация по каждому поисковому модулю
            echo("Check service: $checkService->CheckServiceName Score.White=" .
                $checkService->ScoreByReport->Legal . "% Score.Black=" .
                $checkService->ScoreByReport->Plagiarism . "% Score.SelfCite=" .
                $checkService->ScoreByReport->SelfCite . "%\n");
            if (isset($checkService->Sources)) {
                foreach ($checkService->Sources as $source) {
                    // Информация по каждому найденному источнику
                    echo("\t" . $source->SrcHash . ": Score=" . $source->ScoreByReport . "% (" .
                        $source->ScoreBySource . "%), Name='" . $source->Name . "' " .
                        "Author='" . $source->Author . "' " .
                        "Url='" . $source->Url . "'\n");
                }
            }
        }

        // Получить полный отчет
        $fullReport = $this->client->GetReportView(array(
            "docId" => $id, "options" => array(
                "FullReport" => true, "NeedText" => true, "NeedStats" => true, "NeedAttributes" => true
            )
        ));
        dump($fullReport);
        if (isset($fullReport->GetReportViewResult->Details->CiteBlocks)) {
            // Найти самый большой блок заимствований и вывести его

            $maxlen = max(array_map(function ($c) {
                return $c->Length;
            }, $fullReport->GetReportViewResult->Details->CiteBlocks));

            foreach ($fullReport->GetReportViewResult->Details->CiteBlocks as $block) {
                if ($block->Length === $maxlen) {
                    echo("Max block length=" . $block->Length . " Source=" .
                        $block->SrcHash . " text:\n" .
                        ($fullReport->GetReportViewResult->Details->Text . substr($block->Offset, min($block->Length, 200))) . "...\n");
                }
            }
        }
        return true;
    }

    public function get_web_report($fileContent, $fileName, $EXT, $userID)
    {

        $data = $this->get_doc_data($fileContent, $fileName, $EXT, $userID);
        // Загрузка файла
        $uploadResult = $this->client->UploadDocument(array("data" => $data));

        // Идентификатор документа. Если загружается не архив, то список загруженных документов будет состоять из одного элемента.
        $id = $uploadResult->UploadDocumentResult->Uploaded[0]->Id;

        // Отправить на проверку с использованием всех подключеных компании модулей поиска
        $this->client->CheckDocument(array("docId" => $id));
        // Отправить на проверку с использованием только собственного модуля поиска и модуля поиска "wikipedia". Для получения списка модулей поиска см. пример get_tariff_info()
        // $this->client->CheckDocument(array("docId" => $id, "checkServicesList" => array($COMPANY_NAME, "wikipedia")));

        // Получить текущий статус последней проверки
        $status = $this->client->GetCheckStatus(array("docId" => $id));

        // Цикл ожидания окончания проверки
        while ($status->GetCheckStatusResult->Status === "InProgress") {
            sleep($status->GetCheckStatusResult->EstimatedWaitTime * 0.1);
            $status = $this->client->GetCheckStatus(array("docId" => $id));
        }

        // Проверка закончилась не удачно.
        if ($status->GetCheckStatusResult->Status === "Failed") {
            echo("При проверке документа произошла ошибка:" + $status->GetCheckStatusResult->FailDetails);
            return;
        }

        echo("Full Report: " . $this->url . $status->GetCheckStatusResult->Summary->ReportWebId . "\n");
        echo("Short Report: " . $this->url . $status->GetCheckStatusResult->Summary->ShortReportWebId . "\n");
        echo("Readonly Report: " . $this->url . $status->GetCheckStatusResult->Summary->ReadonlyReportWebId . "\n");
    }


    public function get_doc_data($fileContent, $fileName, $EXT, $userID)
    {
        return array(
            "Data"           => $fileContent,
            "FileName"       => $fileName,
            "FileType"       => "." . $EXT,
            "ExternalUserID" => $userID
        );
    }


    /**
     * @return bool
     */
    private function isEmptyFileInfo(): bool
    {
        return empty($this->fileInfo);
    }

    /**
     * @return DocumentId
     */
    public function getDocId(): DocumentId
    {
        return $this->docId;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
