<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector\Tests;

use GuzzleHttp\ClientInterface;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Argument\Token\CallbackToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;

/**
 * @covers \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator
 * @coversDefaultClass \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator
 */
class Guzzle6ClientDecoratorTest extends TestCase
{
    use ProphecyTrait;

    private const ABSTRACT_NAME = 'my-abstract-name';

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

    protected function setUp(): void
    {
        $this->clientProphecy = $this->prophesize(ClientInterface::class);
        $this->guzzleCollectorProphecy = $this->prophesize(GuzzleCollector::class);

        /**
         * @var \GuzzleHttp\ClientInterface $clientMock
         */
        $clientMock = $this->clientProphecy->reveal();

        /**
         * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector $guzzleCollectorMock
         */
        $guzzleCollectorMock = $this->guzzleCollectorProphecy->reveal();

        $this->guzzle6ClientDecorator = new Guzzle6ClientDecorator($clientMock, $guzzleCollectorMock, self::ABSTRACT_NAME);
    }

    public function testSend(): void
    {
        $requestDummy = $this->prepareRequestDummy();
        $options = [];

        $this->guzzleCollectorProphecy->addRequest(self::ABSTRACT_NAME, 'send', $requestDummy, $options, $this->prepareTimesArgumentAssertion())
            ->shouldBeCalledOnce();

        $this->clientProphecy->send($requestDummy, $options)
            ->shouldBeCalledOnce();

        $this->guzzle6ClientDecorator->send($requestDummy, $options);
    }

    public function testSendAsync(): void
    {
        $requestDummy = $this->prepareRequestDummy();
        $options = [];

        $this->guzzleCollectorProphecy->addRequest(self::ABSTRACT_NAME, 'sendAsync', $requestDummy, $options, $this->prepareTimesArgumentAssertion())
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

        $this->guzzleCollectorProphecy->addRawRequest(self::ABSTRACT_NAME, 'request', $method, $uri, $options, $this->prepareTimesArgumentAssertion())
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

        $this->guzzleCollectorProphecy->addRawRequest(self::ABSTRACT_NAME, 'requestAsync', $method, $uri, $options, $this->prepareTimesArgumentAssertion())
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

    private function prepareRequestDummy(): RequestInterface
    {
        $requestDummy = $this->prophesize(RequestInterface::class)->reveal();

        /**
         * @var \Psr\Http\Message\RequestInterface $requestDummy
         */
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
