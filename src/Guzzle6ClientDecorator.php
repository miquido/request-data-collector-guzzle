<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Guzzle6ClientDecorator extends Client implements ClientInterface
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector
     */
    private $guzzleCollector;

    /**
     * @param \GuzzleHttp\ClientInterface                                              $client
     * @param \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector $guzzleCollector
     */
    public function __construct(ClientInterface $client, GuzzleCollector $guzzleCollector)
    {
        parent::__construct();

        $this->client = $client;
        $this->guzzleCollector = $guzzleCollector;
    }

    /**
     * @inheritdoc
     */
    public function send(RequestInterface $request, array $options = [])
    {
        $TStart = \microtime(true);
        $response = $this->client->send($request, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRequest('send', $request, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $TStart = \microtime(true);
        $promise = $this->client->sendAsync($request, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRequest('sendAsync', $request, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $promise;
    }

    /**
     * @inheritdoc
     */
    public function request($method, $uri = '', array $options = [])
    {
        $TStart = \microtime(true);
        $response = $this->client->request($method, $uri, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRawRequest('request', $method, $uri, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $TStart = \microtime(true);
        $promise = $this->client->requestAsync($method, $uri, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRawRequest('requestAsync', $method, $uri, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $promise;
    }

    /**
     * @inheritdoc
     */
    public function getConfig($option = null)
    {
        return $this->client->getConfig($option);
    }
}
