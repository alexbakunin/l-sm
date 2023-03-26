<?php

namespace App\Services\Author24;

use App\Models\Author24\Account;
use App\Models\Author24\Order;
use App\Models\Files\Files;
use App\Services\Author24\GraphQL\Client\GraphQLClient;
use App\Services\Author24\Repositories\Author24Repository;

class Author24Service
{
    private Account            $account;
    private Author24Repository $repository;

    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->repository = (new Author24Repository(new GraphQLClient($this->account)));
    }

    public function getExternalSourceId()
    {
        return $this->repository->getSourceId();
    }

    public function getSummaryInfoAboutOrdersWithOutBid()
    {
        return $this->repository->getSummaryInfoAboutOrdersWithoutBid();
    }

    public function getNewOrdersWithOutOffer(int $page)
    {
        return $this->repository->getNewOrdersWithOutOffer($page);
    }

    public function getWorkTypesDictionary()
    {
        return $this->repository->getWorkTypesDictionary();
    }

    public function getWorkCategoriesDictionary()
    {
        return $this->repository->getWorkCategoriesDictionary();
    }

    public function getPerformerOrders(int $limit = 50, int $offset = 0)
    {
        return $this->repository->getPerformerOrders($limit, $offset);
    }

    public function getOrderDialog(Order $order)
    {
        return $this->repository->getOrderDialog($order);
    }
    public function getOrderById(int $orderId)
    {
        return $this->repository->getOrderById($orderId);
    }
    public function getNotifications(int $limit = 100, int $offset = 0)
    {
        return $this->repository->getNotifications($limit, $offset);

    }
    public function setOrderBid(Order $order, bool $withText)
    {
        return $this->repository->setOrderBid($order, $withText);
    }

    public function updateOrderWithBid(Order $order, array $data)
    {
        return $order->update($data);
    }

    public function sendMessageToChat(Order $order, string $text)
    {
        return $this->repository->sendMessageToChat($order, $text);
    }

    public function sendFileToChat(Order $order, Files $file, $status = 0)
    {
        $query = $this->repository->sendFileToChatMutation($order, $status);
        $response = \Http::withHeaders(['Token' => $this->account->token])
            ->attach('file0', file_get_contents($file->url), $file->name)
            ->post(config('author24.graphql.endpoint'), [
                'query' => $query
            ]);
        return $response->json();
    }

    public function acceptWork(Order $order)
    {
        try {
            $accept = $this->repository->acceptWork($order);
            $order->update(['inwork' => 'accepted']);
            return $accept;
        } catch (\Throwable $e) {
            \Log::error('Accept work error', [$e->getCode(), $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }
    }
    public function rejectWork(Order $order)
    {
        try {
            $reject = $this->repository->rejectWork($order);
            $order->update(['inwork' => 'rejected']);
            return $reject;
        } catch (\Throwable $e) {
            \Log::error('Reject work error', [$e->getCode(), $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage(), 'code' => $e->getCode()];

        }
    }
}
