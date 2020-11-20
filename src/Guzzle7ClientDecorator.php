<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Guzzle7ClientDecorator extends Client
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
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
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

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
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

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (!$this->client instanceof Client) {
            throw new \BadMethodCallException(\sprintf('Method sendRequest() not found in %s', \get_class($this->client)));
        }

        $TStart = \microtime(true);
        $promise = $this->client->sendRequest($request);
        $TEnd = \microtime(true);

        $this->guzzleCollector->addRequest($this->abstract, 'sendRequest', $request, [], [
            'started_at'  => $TStart,
            'finished_at' => $TEnd,
        ]);

        return $promise;
    }

    /**
     * @param \Psr\Http\Message\UriInterface|string $uri URI object or string.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
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
     * @param \Psr\Http\Message\UriInterface|string $uri URI object or string.
     */
    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
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

    public function getConfig(?string $option = null)
    {
        return $this->client->getConfig($option);
    }
}
