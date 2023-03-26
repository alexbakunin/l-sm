<?php

namespace App\Services\Author24\Repositories;

use App\Models\Author24\Account;
use App\Models\Author24\Order;
use App\Services\Author24\Exception\QueryErrorException;
use App\Services\Author24\GraphQL\Client\GraphQLClient;
use App\Services\Author24\GraphQL\Mutations\AcceptWorkMutation;
use App\Services\Author24\GraphQL\Mutations\RejectWorkMutation;
use App\Services\Author24\GraphQL\Mutations\SendMessageToChatMutation;
use App\Services\Author24\GraphQL\Mutations\SetOrderBidMutation;
use App\Services\Author24\GraphQL\Queries\GetDictionaryListQuery;
use App\Services\Author24\GraphQL\Queries\GetNewOrdersWithOutBid;
use App\Services\Author24\GraphQL\Queries\GetNotificationsQuery;
use App\Services\Author24\GraphQL\Queries\GetOrderByIdQuery;
use App\Services\Author24\GraphQL\Queries\GetOrderDialogQuery;
use App\Services\Author24\GraphQL\Queries\GetPerformerOrdersQuery;
use App\Services\Author24\GraphQL\Queries\GetSummaryInfoForOrdersWithoutBids;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GuzzleHttp\Exception\ConnectException;

class Author24Repository
{
    private Client  $client;
    private Account $account;
    public const SOURCE_ID = 1;

    public function __construct(GraphQLClient $client)
    {
        $this->account = $client->getAccount();
        $this->client = $client->getClient();
    }

    public function getSummaryInfoAboutOrdersWithoutBid()
    {
        try {
            $query = (new GetSummaryInfoForOrdersWithoutBids($this->account->settings))->getQuery();
            $request = $this->client->runQuery(
                $query
            );
            $request->reformatResults(true);
            return $request->getData();

        } catch (QueryError $exception) {
            \Log::stack(['author24'])->error($exception->getMessage(), $exception->getErrorDetails());
            return ['status' => false, 'error' => $exception->getMessage()];
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }


    }

    public function getNewOrdersWithOutOffer(int $page)
    {
        try {
            $query = (new GetNewOrdersWithOutBid($this->account->settings, $page))->getQuery();
            $request = $this->client->runQuery(
                $query
            );
            $request->reformatResults(true);
            return $request->getData();

        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }

    public function getWorkTypesDictionary()
    {
        try {
            $query = (new GetDictionaryListQuery('worktypes'))->getQuery();
            $request = $this->client->runQuery(
                $query
            );
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['dictionarylist']['worktypes'] ?? [];

        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }

    public function getWorkCategoriesDictionary()
    {
        try {
            $query = (new GetDictionaryListQuery('workcategories'))->getQuery();
            $request = $this->client->runQuery(
                $query
            );
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['dictionarylist']['workcategoriesgroup'] ?? [];

        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }

    public function getPerformerOrders(int $limit, int $offset)
    {
        try {
            $query = (new GetPerformerOrdersQuery($limit, $offset))->getQuery();
            $request = $this->client->runQuery($query);
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['getPerformerOrders'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }

    public function getOrderDialog(Order $order)
    {
        try {
            $query = (new GetOrderDialogQuery($order))->getQuery();
            $request = $this->client->runQuery($query);
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['dialog'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }
    public function getOrderById(int $orderId)
    {
        try {
            $query = (new GetOrderByIdQuery($orderId))->getQuery();
            $request = $this->client->runQuery($query);
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['dialog'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }
    public function setOrderBid(Order $order, bool $withText = true)
    {
        try {
            $query = (new SetOrderBidMutation($order, $withText))->getMutation();
            $request = $this->client->runQuery(
                $query
            );
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['orderCreateOffer'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }
    }

    public function sendMessageToChat(Order $order, string $text)
    {
        try {
            $query = (new SendMessageToChatMutation($order, $text))->getMutation();
            $request = $this->client->runQuery(
                $query
            );
            $request->reformatResults(true);
            $data = $request->getData();
            \Log::stack(['author24'])->debug('sendMessageToChat', $data);
            return $data['addComment'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());

        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }
    }

    public function sendFileToChatMutation(Order $order, int $status)
    {
        return (new SendMessageToChatMutation($order, ''))->getMutation()->__toString();
    }

    public function acceptWork(Order $order)
    {
        try {
            $query = (new AcceptWorkMutation($order))->getMutation();
            $request = $this->client->runQuery($query);
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['acceptWork'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }

    public function rejectWork(Order $order)
    {
        try {
            $query = (new RejectWorkMutation($order))->getMutation();
            $request = $this->client->runQuery($query);
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['rejectWork'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }

    }

    public function getNotifications($limit, $offset)
    {
        try {
            $query = (new GetNotificationsQuery($offset, $limit))->getQuery();
            $request = $this->client->runQuery($query);
            $request->reformatResults(true);
            $data = $request->getData();
            return $data['getNotifications'] ?? [];
        } catch (QueryError $exception) {
            throw new QueryErrorException($exception->getMessage(), $query->__toString());
        } catch (ConnectException $exception) {
            \Log::stack(['author24'])->error('Connection error', [$exception->getCode(), $exception->getMessage()]);
            return ['status' => false, 'error' => $exception->getMessage()];

        }
    }

    public function getSourceId()
    {
        return self::SOURCE_ID;
    }
}
