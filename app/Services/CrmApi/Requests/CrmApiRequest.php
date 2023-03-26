<?php

namespace App\Services\CrmApi\Requests;

use GuzzleHttp\Client;

/**
 * Class CrmApiRequest
 *
 * @package App\Services\CrmApi\Requests
 */
class CrmApiRequest
{
    /**
     * @var mixed
     */
    private $token;
    /**
     * @var mixed
     */
    private $url;

    /**
     * CrmApiRequest constructor.
     */
    public function __construct()
    {
        $this->url = env('CRM_API_URL', '');
        $this->token = env('CRM_API_TOKEN', '');
    }

    /**
     * @param  string  $action
     * @param  string  $method
     * @param  array   $data
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function getResponse(string $action, string $method, array $data): array
    {
        $client = new Client(
            [
                'base_uri' => $this->url . "/",
                'timeout'  => 60.0,
            ]
        );

        $data['action'] = $action;
        $data['method'] = $method;
        $data['token'] = md5($this->token . md5(json_encode($data, JSON_THROW_ON_ERROR)));

        $options = [
            'json'    => $data,
        ];
        $response = $client->request('POST', '', $options);

        $response = json_decode($response->getBody()->getContents(), true);

        if (!$response || !is_array($response)) {
            throw new \RuntimeException("Не удалось получить ответ");
        }

        return $response;
    }
}
