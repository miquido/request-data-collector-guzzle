<?php
declare(strict_types=1);

namespace Tests\Collectors\GuzzleCollector;

use GuzzleHttp\ClientInterface;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\RequestInterface;

/**
 * @coversDefaultClass \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator
 */
class Guzzle6ClientDecoratorTest extends TestCase
{
    /**
     * @var \GuzzleHttp\ClientInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    private $clientProphecy;

    /**
     * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector|\Prophecy\Prophecy\ObjectProphecy
     */
    private $guzzleCollectorProphecy;

    /**
     * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator
     */
    private $guzzle6ClientDecorator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->clientProphecy = $this->prophesize(ClientInterface::class);
        $this->guzzleCollectorProphecy = $this->prophesize(GuzzleCollector::class);

        /**
         * @var \GuzzleHttp\ClientInterface                                              $clientMock
         * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector $guzzleCollectorMock
         */
        $clientMock = $this->clientProphecy->reveal();
        $guzzleCollectorMock = $this->guzzleCollectorProphecy->reveal();

        $this->guzzle6ClientDecorator = new Guzzle6ClientDecorator($clientMock, $guzzleCollectorMock);
    }

    public function testSend(): void
    {
        $requestDummy = $this->prepareRequestDummy();
        $options = [];

        $this->guzzleCollectorProphecy->addRequest('send', $requestDummy, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->send($requestDummy, $options)
            ->shouldBeCalledOnce();

        $this->guzzle6ClientDecorator->send($requestDummy, $options);
    }

    public function testSendAsync(): void
    {
        $requestDummy = $this->prepareRequestDummy();
        $options = [];

        $this->guzzleCollectorProphecy->addRequest('sendAsync', $requestDummy, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->sendAsync($requestDummy, $options)
            ->shouldBeCalledOnce();

        $this->guzzle6ClientDecorator->sendAsync($requestDummy, $options);
    }

    public function testRequest(): void
    {
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $options = [];

        $this->guzzleCollectorProphecy->addRawRequest('request', $method, $uri, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->request($method, $uri, $options)
            ->shouldBeCalledOnce();

        $this->guzzle6ClientDecorator->request($method, $uri, $options);
    }

    public function testRequestAsync(): void
    {
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $options = [];

        $this->guzzleCollectorProphecy->addRawRequest('requestAsync', $method, $uri, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->requestAsync($method, $uri, $options)
            ->shouldBeCalledOnce();

        $this->guzzle6ClientDecorator->requestAsync($method, $uri, $options);
    }

    public function testGetConfig(): void
    {
        $option = [];

        $this->clientProphecy->getConfig($option)
            ->shouldBeCalledOnce();

        $this->guzzle6ClientDecorator->getConfig($option);
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    private function prepareRequestDummy(): RequestInterface
    {
        $requestDummy = $this->prophesize(RequestInterface::class)->reveal();

        /**
         * @var \Psr\Http\Message\RequestInterface $requestDummy
         */
        return $requestDummy;
    }

    /**
     * @return \Prophecy\Argument\Token\CallbackToken
     */
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
