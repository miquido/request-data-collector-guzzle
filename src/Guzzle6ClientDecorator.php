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
     * @var string
     */
    private $abstract;

    public function __construct(ClientInterface $client, GuzzleCollector $guzzleCollector, string $abstract)
    {
        parent::__construct();

        $this->client = $client;
        $this->guzzleCollector = $guzzleCollector;
        $this->abstract = $abstract;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send(RequestInterface $request, array $options = [])
    {
        $TStart = \microtime(true);
        $response = $this->client->send($request, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRequest($this->abstract, 'send', $request, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $response;
    }

    /**
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $TStart = \microtime(true);
        $promise = $this->client->sendAsync($request, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRequest($this->abstract, 'sendAsync', $request, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $promise;
    }

    /**
     * @param string                                $method HTTP method.
     * @param \Psr\Http\Message\UriInterface|string $uri    URI object or string.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function request($method, $uri = '', array $options = [])
    {
        $TStart = \microtime(true);
        $response = $this->client->request($method, $uri, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRawRequest($this->abstract, 'request', $method, $uri, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $response;
    }

    /**
     * @param string                                $method HTTP method
     * @param \Psr\Http\Message\UriInterface|string $uri    URI object or string.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri = '', array $options = [])
    {
        $TStart = \microtime(true);
        $promise = $this->client->requestAsync($method, $uri, $options);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRawRequest($this->abstract, 'requestAsync', $method, $uri, $options, [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $promise;
    }

    public function getConfig($option = null)
    {
        return $this->client->getConfig($option);
    }
}
