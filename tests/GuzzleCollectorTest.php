<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector\Tests;

use Illuminate\Contracts\Container\Container;
use Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use stdClass;

/**
 * @covers \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector
 * @coversDefaultClass \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector
 */
class GuzzleCollectorTest extends TestCase
{
    /**
     * @var \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector
     */
    private $guzzleCollector;

    protected function setUp(): void
    {
        $this->guzzleCollector = new GuzzleCollector();
    }

    public function infoDataProvider(): array
    {
        return [
            GuzzleCollector::INFO_BY      => ['info' => GuzzleCollector::INFO_BY],
            GuzzleCollector::INFO_VIA     => ['info' => GuzzleCollector::INFO_VIA],
            GuzzleCollector::INFO_METHOD  => ['info' => GuzzleCollector::INFO_METHOD],
            GuzzleCollector::INFO_URI     => ['info' => GuzzleCollector::INFO_URI],
            GuzzleCollector::INFO_HEADERS => ['info' => GuzzleCollector::INFO_HEADERS],
            GuzzleCollector::INFO_OPTIONS => ['info' => GuzzleCollector::INFO_OPTIONS],
        ];
    }

    public function testRequestForNonExistentAbstractWithCreatingDisallowed(): void
    {
        $abstract = $this->getName();

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'with'      => 'decorator::class',
                'abstracts' => [
                    $abstract => [
                        'type'   => 'singleton',
                        'create' => false,
                    ],
                ],
            ],
        ]);

        $containerProphecy = $this->prepareContainerProphecy();

        $containerProphecy->has($abstract)
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $containerProphecy->make(Argument::type('string'))
            ->shouldNotBeCalled();

        $containerProphecy->singleton($abstract, Argument::any())
            ->shouldNotBeCalled();

        /**
         * @var \Illuminate\Contracts\Container\Container $containerMock
         */
        $containerMock = $containerProphecy->reveal();

        $this->guzzleCollector->register($containerMock);
    }

    public function testRequestForNonExistentAbstractWithCreatingAllowed(): void
    {
        $abstract = $this->getName();

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'with'      => 'decorator::class',
                'abstracts' => [
                    $abstract => [
                        'type'   => 'singleton',
                        'create' => true,
                    ],
                ],
            ],
        ]);

        $containerProphecy = $this->prepareContainerProphecy();

        $containerProphecy->has($abstract)
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $containerProphecy->singleton(\sprintf('request-data-collector.old-abstract.%s', $abstract), $abstract)
            ->shouldBeCalledOnce();

        $objectDummy = $this->prophesize(stdClass::class)->reveal();

        $containerProphecy->make(\sprintf('request-data-collector.old-abstract.%s', $abstract))
            ->shouldBeCalledOnce()
            ->willReturn($objectDummy);

        $this->assertAbstractWasDecorated($containerProphecy, $abstract, $objectDummy);

        /**
         * @var \Illuminate\Contracts\Container\Container $containerMock
         */
        $containerMock = $containerProphecy->reveal();

        $this->guzzleCollector->register($containerMock);
    }

    public function testRequestForExistingAbstractWithCreatingDisallowed(): void
    {
        $abstract = $this->getName();

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'with'      => 'decorator::class',
                'abstracts' => [
                    $abstract => [
                        'type'   => 'singleton',
                        'create' => false,
                    ],
                ],
            ],
        ]);

        $containerProphecy = $this->prepareContainerProphecy();

        $containerProphecy->has($abstract)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $objectDummy = $this->prophesize(stdClass::class)->reveal();

        $containerProphecy->make($abstract)
            ->shouldBeCalledOnce()
            ->willReturn($objectDummy);

        $this->assertAbstractWasRewritten($containerProphecy, $abstract, $objectDummy);
        $this->assertAbstractWasDecorated($containerProphecy, $abstract, $objectDummy);

        /**
         * @var \Illuminate\Contracts\Container\Container $containerMock
         */
        $containerMock = $containerProphecy->reveal();

        $this->guzzleCollector->register($containerMock);
    }

    public function testRequestForExistingAbstractWithCreatingAllowed(): void
    {
        $abstract = $this->getName();

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'with'      => 'decorator::class',
                'abstracts' => [
                    $abstract => [
                        'type'   => 'singleton',
                        'create' => true,
                    ],
                ],
            ],
        ]);

        $containerProphecy = $this->prepareContainerProphecy();

        $containerProphecy->has($abstract)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $objectDummy = $this->prophesize(stdClass::class)->reveal();

        $containerProphecy->make($abstract)
            ->shouldBeCalledOnce()
            ->willReturn($objectDummy);

        $this->assertAbstractWasRewritten($containerProphecy, $abstract, $objectDummy);
        $this->assertAbstractWasDecorated($containerProphecy, $abstract, $objectDummy);

        /**
         * @var \Illuminate\Contracts\Container\Container $containerMock
         */
        $containerMock = $containerProphecy->reveal();

        $this->guzzleCollector->register($containerMock);
    }

    /**
     * @covers ::addRequest
     * @covers ::collect
     * @covers ::registerRequest
     */
    public function testAddRequest(): void
    {
        $abstract = $this->getName();
        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $headers = [
            'Header' => [
                'value1',
                'value2',
            ],
        ];
        $options = [];

        /**
         * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Http\Message\RequestInterface $requestProphecy
         */
        $requestProphecy = $this->prophesize(RequestInterface::class);

        $requestProphecy->getMethod()
            ->shouldBeCalledOnce()
            ->willReturn($method);

        $requestProphecy->getUri()
            ->shouldBeCalledOnce()
            ->willReturn($uri);

        $requestProphecy->getHeaders()
            ->shouldBeCalledOnce()
            ->willReturn($headers);

        /**
         * @var \Psr\Http\Message\RequestInterface $requestMock
         */
        $requestMock = $requestProphecy->reveal();

        $this->guzzleCollector->addRequest($abstract, $via, $requestMock, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            [
                'by'      => $abstract,
                'via'     => $via,
                'method'  => $method,
                'uri'     => $uri,
                'headers' => $headers,
                'options' => $options,

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ],
        ], $this->guzzleCollector->collect());
    }

    /**
     * @dataProvider infoDataProvider
     *
     * @covers ::addRequest
     * @covers ::collect
     * @covers ::registerRequest
     */
    public function testAddRequestWithInfoIncluded(string $info): void
    {
        $abstract = $this->getName();

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'abstracts' => [
                    $abstract => [
                        'collect' => [$info],
                    ],
                ],
            ],
        ]);

        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $headers = [
            'Header' => [
                'value1',
                'value2',
            ],
        ];
        $options = [];

        /**
         * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Http\Message\RequestInterface $requestProphecy
         */
        $requestProphecy = $this->prophesize(RequestInterface::class);

        $requestProphecy->getMethod()
            ->shouldBeCalledOnce()
            ->willReturn($method);

        $requestProphecy->getUri()
            ->shouldBeCalledOnce()
            ->willReturn($uri);

        $requestProphecy->getHeaders()
            ->shouldBeCalledOnce()
            ->willReturn($headers);

        /**
         * @var \Psr\Http\Message\RequestInterface $requestMock
         */
        $requestMock = $requestProphecy->reveal();

        $this->guzzleCollector->addRequest($abstract, $via, $requestMock, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            \array_intersect_key([
                'by'      => $abstract,
                'via'     => $via,
                'method'  => $method,
                'uri'     => $uri,
                'headers' => $headers,
                'options' => $options,

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ], [$info => true, 'times' => true]),
        ], $this->guzzleCollector->collect());
    }

    /**
     * @covers ::addRawRequest
     * @covers ::collect
     * @covers ::registerRequest
     */
    public function testAddRawRequest(): void
    {
        $abstract = $this->getName();
        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $options = [];

        $this->guzzleCollector->addRawRequest($abstract, $via, $method, $uri, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            [
                'by'      => $abstract,
                'via'     => $via,
                'method'  => $method,
                'uri'     => $uri,
                'headers' => [],
                'options' => $options,

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ],
        ], $this->guzzleCollector->collect());
    }

    /**
     * @dataProvider infoDataProvider
     *
     * @covers ::addRawRequest
     * @covers ::collect
     * @covers ::registerRequest
     */
    public function testAddRawRequestWithInfoIncluded(string $info): void
    {
        $abstract = $this->getName();

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'abstracts' => [
                    $abstract => [
                        'collect' => [$info],
                    ],
                ],
            ],
        ]);

        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $options = [];

        $this->guzzleCollector->addRawRequest($abstract, $via, $method, $uri, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            \array_intersect_key([
                'by'      => $abstract,
                'via'     => $via,
                'method'  => $method,
                'uri'     => $uri,
                'headers' => [],
                'options' => $options,

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ], [$info => true, 'times' => true]),
        ], $this->guzzleCollector->collect());
    }

    /**
     * @covers ::addRequest
     * @covers ::collect
     * @covers ::registerRequest
     * @covers ::filterHeaders
     */
    public function testAddRequestHeadersAreRestrictedToIncludedOnlyAndOverrideExclusions(): void
    {
        $abstract = $this->getName();

        $header1Name = 'Header1';
        $header2Name = 'HEADER-2';
        $header3Name = 'header-3';
        $header4Name = 'Header4';

        $header1Value = [
            'value1',
            'value2',
        ];
        $header2Value = [
            'value3',
            'value4',
        ];
        $header3Value = [
            'value5',
            'value6',
        ];
        $header4Value = [
            'value7',
            'value8',
        ];

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'abstracts' => [
                    $abstract => [
                        'collect' => [
                            GuzzleCollector::INFO_HEADERS => [
                                'excludes' => [
                                    $header4Name,
                                    $header1Name,
                                ],

                                'includes' => [
                                    $header1Name,
                                    $header2Name,
                                    $header3Name,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $headers = [
            $header1Name => $header1Value,
            $header2Name => $header2Value,
            $header3Name => $header3Value,
            $header4Name => $header4Value,
        ];
        $options = [];

        /**
         * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Http\Message\RequestInterface $requestProphecy
         */
        $requestProphecy = $this->prophesize(RequestInterface::class);

        $requestProphecy->getMethod()
            ->shouldBeCalledOnce()
            ->willReturn($method);

        $requestProphecy->getUri()
            ->shouldBeCalledOnce()
            ->willReturn($uri);

        $requestProphecy->getHeaders()
            ->shouldBeCalledOnce()
            ->willReturn($headers);

        /**
         * @var \Psr\Http\Message\RequestInterface $requestMock
         */
        $requestMock = $requestProphecy->reveal();

        $this->guzzleCollector->addRequest($abstract, $via, $requestMock, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            [
                'headers' => [
                    $header1Name => $header1Value,
                    $header2Name => $header2Value,
                    $header3Name => $header3Value,
                ],

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ],
        ], $this->guzzleCollector->collect());
    }

    /**
     * @covers ::addRequest
     * @covers ::collect
     * @covers ::registerRequest
     * @covers ::filterHeaders
     */
    public function testAddRequestHeadersAreExcluded(): void
    {
        $abstract = $this->getName();

        $header1Name = 'Header1';
        $header2Name = 'HEADER-2';
        $header3Name = 'header-3';
        $header4Name = 'Header4';

        $header1Value = [
            'value1',
            'value2',
        ];
        $header2Value = [
            'value3',
            'value4',
        ];
        $header3Value = [
            'value5',
            'value6',
        ];
        $header4Value = [
            'value7',
            'value8',
        ];

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'abstracts' => [
                    $abstract => [
                        'collect' => [
                            GuzzleCollector::INFO_HEADERS => [
                                'excludes' => [
                                    $header4Name,
                                    $header1Name,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $headers = [
            $header1Name => $header1Value,
            $header2Name => $header2Value,
            $header3Name => $header3Value,
            $header4Name => $header4Value,
        ];
        $options = [];

        /**
         * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Http\Message\RequestInterface $requestProphecy
         */
        $requestProphecy = $this->prophesize(RequestInterface::class);

        $requestProphecy->getMethod()
            ->shouldBeCalledOnce()
            ->willReturn($method);

        $requestProphecy->getUri()
            ->shouldBeCalledOnce()
            ->willReturn($uri);

        $requestProphecy->getHeaders()
            ->shouldBeCalledOnce()
            ->willReturn($headers);

        /**
         * @var \Psr\Http\Message\RequestInterface $requestMock
         */
        $requestMock = $requestProphecy->reveal();

        $this->guzzleCollector->addRequest($abstract, $via, $requestMock, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            [
                'headers' => [
                    $header2Name => $header2Value,
                    $header3Name => $header3Value,
                ],

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ],
        ], $this->guzzleCollector->collect());
    }

    /**
     * @covers ::addRequest
     * @covers ::collect
     * @covers ::registerRequest
     * @covers ::filterHeaders
     */
    public function testAddRequestHeadersAreMaintainedWhenEmptyConfigurationPassed(): void
    {
        $abstract = $this->getName();

        $header1Name = 'Header1';
        $header2Name = 'HEADER-2';
        $header3Name = 'header-3';
        $header4Name = 'Header4';

        $header1Value = [
            'value1',
            'value2',
        ];
        $header2Value = [
            'value3',
            'value4',
        ];
        $header3Value = [
            'value5',
            'value6',
        ];
        $header4Value = [
            'value7',
            'value8',
        ];

        $this->guzzleCollector->setConfig([
            'decorate' => [
                'abstracts' => [
                    $abstract => [
                        'collect' => [
                            GuzzleCollector::INFO_HEADERS => [],
                        ],
                    ],
                ],
            ],
        ]);

        $via = 'method_name';
        $method = 'METHOD';
        $uri = 'uri://example.com';
        $headers = [
            $header1Name => $header1Value,
            $header2Name => $header2Value,
            $header3Name => $header3Value,
            $header4Name => $header4Value,
        ];
        $options = [];

        /**
         * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Http\Message\RequestInterface $requestProphecy
         */
        $requestProphecy = $this->prophesize(RequestInterface::class);

        $requestProphecy->getMethod()
            ->shouldBeCalledOnce()
            ->willReturn($method);

        $requestProphecy->getUri()
            ->shouldBeCalledOnce()
            ->willReturn($uri);

        $requestProphecy->getHeaders()
            ->shouldBeCalledOnce()
            ->willReturn($headers);

        /**
         * @var \Psr\Http\Message\RequestInterface $requestMock
         */
        $requestMock = $requestProphecy->reveal();

        $this->guzzleCollector->addRequest($abstract, $via, $requestMock, $options, [
            'started_at'  => 123.45,
            'finished_at' => 678.90,
        ]);

        self::assertEquals([
            [
                'headers' => $headers,

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ],
        ], $this->guzzleCollector->collect());
    }

    public function testGetSeparateLogEntries(): void
    {
        $requests = [
            [
                'via'     => 'method_name',
                'method'  => 'METHOD',
                'uri'     => 'uri://example.com',
                'headers' => [
                    'Header' => [
                        'value1',
                        'value2',
                    ],
                ],
                'options' => [],

                'times' => [
                    'started_at'  => 123.45,
                    'finished_at' => 678.90,
                ],
            ],
            [
                'via'     => 'method_name',
                'method'  => 'METHOD',
                'uri'     => 'uri://example.com',
                'headers' => [],
                'options' => [],

                'times' => [
                    'started_at'  => 234.56,
                    'finished_at' => 789.01,
                ],
            ],
        ];

        foreach ($this->guzzleCollector->getSeparateLogEntries($requests) as $index => $logEntry) {
            self::assertSame((string) $index, $index);
            self::assertSame($requests[$index], $logEntry);
        }
    }

    /**
     * @return \Illuminate\Contracts\Container\Container|\Prophecy\Prophecy\ObjectProphecy
     */
    private function prepareContainerProphecy(): object
    {
        /**
         * @var \Illuminate\Contracts\Container\Container|\Prophecy\Prophecy\ObjectProphecy $containerProphecy
         */
        $containerProphecy = $this->prophesize(Container::class);

        $self = $this;

        $containerProphecy->singleton(GuzzleCollector::class, Argument::type('callable'))
            ->shouldBeCalledOnce()
            ->will(function (array $args) use ($self): object {
                /**
                 * @var callable $concrete
                 */
                [, $concrete] = $args;

                $self::assertSame($self->guzzleCollector, $concrete());

                return $self->guzzleCollector;
            });

        return $containerProphecy;
    }

    /**
     * @param \Illuminate\Contracts\Container\Container|\Prophecy\Prophecy\ObjectProphecy $containerProphecy
     */
    private function assertAbstractWasDecorated($containerProphecy, string $abstract, object $object): void
    {
        $self = $this;

        $containerProphecy->singleton($abstract, Argument::type('callable'))
            ->shouldBeCalledOnce()
            ->will(function (array $args, $containerProphecy) use ($self, $object): object {
                /**
                 * @var callable                                                                    $concrete
                 * @var \Illuminate\Contracts\Container\Container|\Prophecy\Prophecy\ObjectProphecy $containerProphecy
                 */
                [, $concrete] = $args;

                $containerProphecy->make('decorator::class', ['client' => $object])
                    ->shouldBeCalledOnce()
                    ->willReturn($self->prophesize(stdClass::class)->reveal());

                return $concrete();
            });
    }

    /**
     * @param \Illuminate\Contracts\Container\Container|\Prophecy\Prophecy\ObjectProphecy $containerProphecy
     */
    private function assertAbstractWasRewritten($containerProphecy, string $abstract, object $object): void
    {
        $self = $this;

        $containerProphecy->singleton(\sprintf('request-data-collector.old-abstract.%s', $abstract), Argument::type('callable'))
            ->shouldBeCalledOnce()
            ->will(function (array $args) use ($self, $object): object {
                /**
                 * @var callable $concrete
                 */
                [, $concrete] = $args;

                $self::assertSame($object, $concrete());

                return $object;
            });
    }
}
