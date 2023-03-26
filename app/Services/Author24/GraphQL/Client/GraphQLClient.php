<?php

namespace App\Services\Author24\GraphQL\Client;

use App\Models\Author24\Account;
use App\Services\Author24\GraphQL\Mutations\LoginMutation;
use App\Services\Author24\GraphQL\Queries\GetProfile;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Results;
use Log;

class GraphQLClient
{
    private Client  $client;
    private Account $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->client = new Client(config('author24.graphql.endpoint'),
            [],
            [
                'connect_timeout' => 5,
                'timeout'         => 5,
                'headers'         => [
                    'Token' => $this->account->token,
                ]
            ]);
        try {
            $profile = $this->checkAuth();
        } catch (QueryError $exception) {
            Log::stack(['author24'])->error('[checkAuth] Query error: ' . $exception->getMessage());
            $newToken = $this->getAuthToken();
            if ($newToken) {
                $this->updateClient($newToken);
            }
        }
    }

    private function getAuthToken()
    {
        $unAuthClient = new Client(config('author24.graphql.endpoint'));
        $auth = new LoginMutation($this->account);
        try {
            $response = $unAuthClient->runQuery($auth->getMutation())->getResults();
            return $response->data->login->token;
        } catch (QueryError $exception) {
            Log::stack(['author24'])->error('[getAuthToken] Query error: ' . $exception->getMessage());
            throw new \Exception('Ошибка API: ' . $exception->getMessage());
        }
    }

    private function checkAuth(): Results
    {
        return $this->client->runQuery((new GetProfile())->getQuery());
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    private function updateClient($token)
    {
        $account = $this->account->update(['token' => $token]);
        $this->client = new Client(config('author24.graphql.endpoint'),
            [],
            [
                'connect_timeout' => 5,
                'timeout'         => 5,
                'headers'         => [
                    'Token' => $token,
                ]
            ]);
    }

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }
}
