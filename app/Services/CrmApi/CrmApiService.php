<?php

namespace App\Services\CrmApi;

use App\Services\CrmApi\Requests\CrmApiRequest;

/**
 * Class CrmApiService
 *
 * @package App\Services\CrmApi
 */
class CrmApiService
{
    /**
     * @var CrmApiRequest
     */
    private CrmApiRequest $request;

    /**
     * CrmApiService constructor.
     *
     * @param CrmApiRequest $request
     */
    public function __construct(CrmApiRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param float $sum
     * @param int $userId
     * @param int $contractId
     * @param int $isRefund
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function sendDreamkasAdd(float $sum, int $userId, int $contractId, int $isRefund)
    {
        return $this->request->getResponse(
            'Dreamkas',
            'add',
            [
                'sum'         => $sum,
                'user_id'     => $userId,
                'contract_id' => $contractId,
                'isRefund'    => $isRefund
            ]
        );
    }

    public function sendOrdersLog(array $data = [])
    {
        return $this->request->getResponse(
            'OrdersLog',
            'add',
            [
                'order'   => $data['order'],
                'user_id' => $data['user_id'],
                'comment' => $data['comment'],
                'event'   => $data['event'],
            ]
        );
    }


    public function sendAntiplagiatCheckReport(int $fileCheckId)
    {
        return $this->request->getResponse(
            'Antiplagiat',
            'SuccessReport',
            [
                'file_check_id' => $fileCheckId,
            ]
        );
    }

    public function confirmAntiplagiatCheck(int $fileCheckId)
    {
        return $this->request->getResponse(
            'Antiplagiat',
            'ConfirmFileCheck',
            [
                'file_check_id' => $fileCheckId,
            ]
        );
    }

    public function saveDebugLog(string $fileName, string $body)
    {
        return $this->request->getResponse(
            'Logger',
            'save',
            [
                'filename' => $fileName,
                'body'     => $body,
            ]
        );
    }

    public function createOrder(array $data = [])
    {
        return $this->request->getResponse(
            'Orders',
            'create',
            $data
        );
    }

    public function updateOrderHistory(int $orderId, array $changes = [])
    {
        return $this->request->getResponse(
            'Orders',
            'orderUpdated',
            ['order_id' => $orderId, 'changes' => $changes]
        );
    }

    public function setOrderStatus(int $orderId, string $status)
    {
        return $this->request->getResponse(
            'Orders',
            'setStatus',
            ['order_id' => $orderId, 'status' => $status]
        );
    }

    public function sendFilesToOrder(int $orderId, array $files)
    {
        return $this->request->getResponse(
            'Orders',
            'downloadExternalFiles',
            ['order_id' => $orderId, 'files' => $files]
        );
    }
}
