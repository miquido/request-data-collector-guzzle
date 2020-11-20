# Request Data Collector - Guzzle collector

This package is an extension to the Request Data Collector. Allows collecting Guzzle requests.

[![GitHub license](https://img.shields.io/badge/license-Apache2.0-brightgreen.svg)](https://github.com/miquido/request-data-collector-guzzle/blob/master/LICENSE)
[![Build](https://github.com/miquido/request-data-collector-guzzle/workflows/PHP/badge.svg?branch=master)](https://github.com/miquido/request-data-collector-guzzle/actions?query=branch%3Amaster)

## Set up

### GuzzleCollector

This collector is being used to collect data about performed Guzzle requests.

```php
'guzzle' => [
	'driver' => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::class,

	'decorate' => [
		'with' => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator::class,

		'abstracts' => [
			abstract => [
				'type'    => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::TYPE_*,
				'create'  => boolean,
				'collect' => [
					\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_BY,
					\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_VIA,
					\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_METHOD,
					\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_URI,
					\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_HEADERS,
					\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_OPTIONS,
				],
			],
			
			// ...
		],
	],
],
```

#### decorate.with

Defines Guzzle Client decorator class responsible for collecting requests.

#### decorate.abstracts

Defines a list of Guzzle Clients registered in the container. All abstracts will be decorated with class defined in `decorate.with`.

Every abstract is being defined in following way:

```php
abstract => [
	'type'   => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::TYPE_*,
	'create' => boolean,
	'collect' => [
		// \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_*
	],
],
```

**abstract** is the name under which instance has been registered in the container (e.g. `my-guzzle-client` or `\GuzzleHttp\ClientInterface::class`).

**type** defines the type of abstract (see `\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::TYPE_*` constants).

**collect** defines list of request's information that should be logged (see `\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_*` constants). If missing, all information will be used. The `times` information will always be available.

Additionally, You can include and exclude headers (case-insensitive). Remember, that **inclusions have priority over exclusions**.

```php
abstract => [
	// ...

	'collect' => [
		\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::INFO_HEADERS => [
			// We don't want Authorization header to be present in logs
			'excludes' => [
				'Authorization'
			],

			// From all headers that has been sent, only those are interesting for us
			'includes' => [
				'Accept',
				'content-type',
			],
		],
	],
],
```

When **create** equals `true`, defines to add an instance to the container even if it does not exist.
