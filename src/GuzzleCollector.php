<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector;

use Illuminate\Contracts\Container\Container;
use Miquido\RequestDataCollector\Collectors\Contracts\ConfigurableInterface;
use Miquido\RequestDataCollector\Collectors\Contracts\DataCollectorInterface;
use Miquido\RequestDataCollector\Collectors\Contracts\ModifiesContainerInterface;
use Miquido\RequestDataCollector\Collectors\Contracts\SupportsSeparateLogEntriesInterface;
use Miquido\RequestDataCollector\Traits\ConfigurableTrait;
use Psr\Http\Message\RequestInterface;

class GuzzleCollector implements DataCollectorInterface, ConfigurableInterface, ModifiesContainerInterface, SupportsSeparateLogEntriesInterface
{
    use ConfigurableTrait;

    public const TYPE_BIND = 'bind';

    public const TYPE_SINGLETON = 'singleton';

    /**
     * The name (registered in the container) of abstract that performed request.
     */
    public const INFO_BY = 'by';

    /**
     * The Guzzle method that was used to send request (send, sendAsync, request or requestAsync).
     */
    public const INFO_VIA = 'via';

    /**
     * Request method.
     */
    public const INFO_METHOD = 'method';

    /**
     * Request target URI.
     */
    public const INFO_URI = 'uri';

    /**
     * Headers sent with request.
     */
    public const INFO_HEADERS = 'headers';

    /**
     * Options configured for request.
     */
    public const INFO_OPTIONS = 'options';

    /**
     * @var array
     */
    private $requests = [];

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register(Container $container): void
    {
        $container->singleton(self::class, function (): self {
            return $this;
        });

        $abstractsMap = [];

        foreach ($this->config['decorate']['abstracts'] as $abstract => ['type' => $type, 'create' => $create]) {
            $hasAbstract = $container->has($abstract);

            if (!$hasAbstract && !$create) {
                continue;
            }

            $oldAbstract = \sprintf('request-data-collector.old-abstract.%s', $abstract);

            if (!$hasAbstract) {
                $container->{$type}($oldAbstract, $abstract);

                $oldInstance = $container->make($oldAbstract);
            } else {
                $oldInstance = $container->make($abstract);

                $container->{$type}($oldAbstract, static function () use (&$oldInstance): object {
                    return $oldInstance;
                });
            }

            $abstractsMap[] = [
                'abstract'    => $abstract,
                'type'        => $type,
                'oldInstance' => $oldInstance,
            ];
        }

        foreach ($abstractsMap as ['abstract' => $abstract, 'type' => $type, 'oldInstance' => $instance]) {
            $container->{$type}($abstract, function () use ($container, $instance): object {
                return $container->make($this->config['decorate']['with'], [
                    'client' => $instance,
                ]);
            });
        }
    }

    public function collect(): array
    {
        return $this->requests;
    }

    public function getSeparateLogEntries(array $collected): iterable
    {
        foreach ($collected as $index => $request) {
            yield (string) $index => $request;
        }
    }

    public function addRequest(string $by, string $via, RequestInterface $request, array $options, array $times): void
    {
        $this->registerRequest($by, [
            self::INFO_BY      => $by,
            self::INFO_VIA     => $via,
            self::INFO_METHOD  => $request->getMethod(),
            self::INFO_URI     => (string) $request->getUri(),
            self::INFO_HEADERS => $request->getHeaders(),
            self::INFO_OPTIONS => $options,
            'times'            => $times,
        ]);
    }

    /**
     * @param \Psr\Http\Message\UriInterface|string $uri
     */
    public function addRawRequest(string $by, string $via, string $method, $uri, array $options, array $times): void
    {
        $this->registerRequest($by, [
            self::INFO_BY      => $by,
            self::INFO_VIA     => $via,
            self::INFO_METHOD  => $method,
            self::INFO_URI     => (string) $uri,
            self::INFO_HEADERS => [],
            self::INFO_OPTIONS => $options,
            'times'            => $times,
        ]);
    }

    protected function registerRequest(string $abstract, array $data): void
    {
        if (!$this->shouldRequestInfoBeCollected($abstract, self::INFO_BY)) {
            unset($data[self::INFO_BY]);
        }

        if (!$this->shouldRequestInfoBeCollected($abstract, self::INFO_VIA)) {
            unset($data[self::INFO_VIA]);
        }

        if (!$this->shouldRequestInfoBeCollected($abstract, self::INFO_METHOD)) {
            unset($data[self::INFO_METHOD]);
        }

        if (!$this->shouldRequestInfoBeCollected($abstract, self::INFO_URI)) {
            unset($data[self::INFO_URI]);
        }

        if ($this->shouldRequestInfoBeCollected($abstract, self::INFO_HEADERS)) {
            $data[self::INFO_HEADERS] = $this->filterHeaders($abstract, $data[self::INFO_HEADERS]);
        } else {
            unset($data[self::INFO_HEADERS]);
        }

        if (!$this->shouldRequestInfoBeCollected($abstract, self::INFO_OPTIONS)) {
            unset($data[self::INFO_OPTIONS]);
        }

        $this->requests[] = $data;
    }

    private function shouldRequestInfoBeCollected(string $abstract, string $info): bool
    {
        if (!isset($this->config['decorate']['abstracts'][$abstract]['collect'])) {
            return true;
        }

        return isset($this->config['decorate']['abstracts'][$abstract]['collect'][$info]) ||
            \in_array($info, $this->config['decorate']['abstracts'][$abstract]['collect'], true);
    }

    /**
     * @param array[] $headers
     *
     * @return array[]
     */
    private function filterHeaders(string $abstract, array $headers): array
    {
        static $cache = [];

        if (!isset($this->config['decorate']['abstracts'][$abstract]['collect'][self::INFO_HEADERS])) {
            return $headers;
        }

        if (!isset($cache[$abstract])) {
            $cache[$abstract] = [
                'includes' => null,
                'excludes' => null,
            ];

            if (isset($this->config['decorate']['abstracts'][$abstract]['collect'][self::INFO_HEADERS]['includes'])) {
                $cache[$abstract]['includes'] = \array_change_key_case(\array_flip($this->config['decorate']['abstracts'][$abstract]['collect'][self::INFO_HEADERS]['includes']), CASE_LOWER);
            } elseif (isset($this->config['decorate']['abstracts'][$abstract]['collect'][self::INFO_HEADERS]['excludes'])) {
                $cache[$abstract]['excludes'] = \array_change_key_case(\array_flip($this->config['decorate']['abstracts'][$abstract]['collect'][self::INFO_HEADERS]['excludes']), CASE_LOWER);
            }
        }

        if (null !== $cache[$abstract]['includes']) {
            foreach ($headers as $name => $data) {
                if (!\array_key_exists(\strtolower($name), $cache[$abstract]['includes'])) {
                    unset($headers[$name]);
                }
            }

            return $headers;
        }

        if (null !== $cache[$abstract]['excludes']) {
            foreach ($headers as $name => $data) {
                if (\array_key_exists(\strtolower($name), $cache[$abstract]['excludes'])) {
                    unset($headers[$name]);
                }
            }

            return $headers;
        }

        return $headers;
    }
}
