<?php
declare(strict_types=1);

namespace Miquido\RequestDataCollector\Collectors\GuzzleCollector;

use Illuminate\Contracts\Container\Container;
use Miquido\RequestDataCollector\Collectors\Contracts\ConfigurableInterface;
use Miquido\RequestDataCollector\Collectors\Contracts\DataCollectorInterface;
use Miquido\RequestDataCollector\Collectors\Contracts\ModifiesContainerInterface;
use Miquido\RequestDataCollector\Traits\ConfigurableTrait;
use Psr\Http\Message\RequestInterface;

class GuzzleCollector implements DataCollectorInterface, ConfigurableInterface, ModifiesContainerInterface
{
    use ConfigurableTrait;

    public const TYPE_BIND = 'bind';

    public const TYPE_SINGLETON = 'singleton';

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

    public function addRequest(string $via, RequestInterface $request, array $options, array $times): void
    {
        $this->requests[] = [
            'via'     => $via,
            'method'  => $request->getMethod(),
            'uri'     => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
            'options' => $options,
            'times'   => $times,
        ];
    }

    /**
     * @param \Psr\Http\Message\UriInterface|string $uri
     */
    public function addRawRequest(string $via, string $method, $uri, array $options, array $times): void
    {
        $this->requests[] = [
            'via'     => $via,
            'method'  => $method,
            'uri'     => (string) $uri,
            'headers' => [],
            'options' => $options,
            'times'   => $times,
        ];
    }
}
