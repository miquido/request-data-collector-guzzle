<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle7ClientDecorator;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Argument\Token\CallbackToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;

/**
 * @covers \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle7ClientDecorator
 * @coversDefaultClass \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle7ClientDecorator
 */
class Guzzle7ClientDecoratorTest extends TestCase
{
    use ProphecyTrait;

    private const ABSTRACT_NAME = 'my-abstract-name';

    /**
     * @var \GuzzleHttp\Client|\GuzzleHttp\ClientInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    private $clientProphecy;

    /**
     * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector|\Prophecy\Prophecy\ObjectProphecy
     */
    private $guzzleCollectorProphecy;

    /**
     * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle7ClientDecorator
     */
    private $guzzle7ClientDecorator;

    protected function setUp(): void
    {
        if (!\defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION') || 7 !== \constant('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) {
            self::markTestSkipped('Guzzle7ClientDecorator should be used with Guzzle 7');
        }

        $this->clientProphecy = $this->prophesize(Client::class);
        $this->guzzleCollectorProphecy = $this->prophesize(GuzzleCollector::class);

        /**
         * @var \GuzzleHttp\ClientInterface $clientMock
         */
        $clientMock = $this->clientProphecy->reveal();

        /**
         * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector $guzzleCollectorMock
         */
        $guzzleCollectorMock = $this->guzzleCollectorProphecy->reveal();

        $this->guzzle7ClientDecorator = new Guzzle7ClientDecorator($clientMock, $guzzleCollectorMock, self::ABSTRACT_NAME);
    }

    public function testSend(): void
    {
        $requestDummy = $this->prepareRequestDummy();
        $options = [];

        $this->guzzleCollectorProphecy->addRequest(self::ABSTRACT_NAME, 'send', $requestDummy, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->send($requestDummy, $options)
            ->shouldBeCalledOnce();

        $this->guzzle7ClientDecorator->send($requestDummy, $options);
    }

    public function testSendAsync(): void
    {
        $requestDummy = $this->prepareRequestDummy();
        $options = [];

        $this->guzzleCollectorProphecy->addRequest(self::ABSTRACT_NAME, 'sendAsync', $requestDummy, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->sendAsync($requestDummy, $options)
            ->shouldBeCalledOnce();

        $this->guzzle7ClientDecorator->sendAsync($requestDummy, $options);
    }

    public function testSendRequest(): void
    {
        $requestDummy = $this->prepareRequestDummy();

        $this->guzzleCollectorProphecy->addRequest(self::ABSTRACT_NAME, 'sendRequest', $requestDummy, [], $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->sendRequest($requestDummy)
            ->shouldBeCalledOnce();

        $this->guzzle7ClientDecorator->sendRequest($requestDummy);
    }

    public function testSendRequestFailsWithUnsupportedClient(): void
    {
        /**
         * @var \GuzzleHttp\ClientInterface $client
         */
        $client = $this->prophesize(ClientInterface::class)->reveal();

        /**
         * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector $guzzleCollector
         */
        $guzzleCollector = $this->prophesize(GuzzleCollector::class)->reveal();

        $guzzle7ClientDecorator = new Guzzle7ClientDecorator($client, $guzzleCollector, self::ABSTRACT_NAME);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(\sprintf('Method sendRequest() not found in %s', \get_class($client)));

        $guzzle7ClientDecorator->sendRequest($this->prepareRequestDummy());
    }

    public function testRequest(): void
    {
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $options = [];

        $this->guzzleCollectorProphecy->addRawRequest(self::ABSTRACT_NAME, 'request', $method, $uri, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->request($method, $uri, $options)
            ->shouldBeCalledOnce();

        $this->guzzle7ClientDecorator->request($method, $uri, $options);
    }

    public function testRequestAsync(): void
    {
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $options = [];

        $this->guzzleCollectorProphecy->addRawRequest(self::ABSTRACT_NAME, 'requestAsync', $method, $uri, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->requestAsync($method, $uri, $options)
            ->shouldBeCalledOnce();

        $this->guzzle7ClientDecorator->requestAsync($method, $uri, $options);
    }

    public function testGetConfig(): void
    {
        $option = 'some-config-key';

        $this->clientProphecy->getConfig($option)
            ->shouldBeCalledOnce();

        $this->guzzle7ClientDecorator->getConfig($option);
    }

    private function prepareRequestDummy(): RequestInterface
    {
        /**
         * @var \Psr\Http\Message\RequestInterface $requestDummy
         */
        $requestDummy = $this->prophesize(RequestInterface::class)->reveal();

        return $requestDummy;
    }

    private function prepareTimesArgumentAssertion(): CallbackToken
    {
        return Argument::that(function ($value) {
            self::assertIsArray($value);
            self::assertArrayHasKey('started_at', $value);
            self::assertArrayHasKey('finished_at', $value);
            self::assertIsFloat($value['started_at']);
            self::assertIsFloat($value['finished_at']);
            self::assertGreaterThan($value['started_at'], $value['finished_at']);

            return true;
        });
    }
}
