# Request Data Collector - Guzzle collector

This package is an extension to the Request Data Collector. Allows collecting Guzzle requests.

[![GitHub license](https://img.shields.io/badge/license-Apache2.0-brightgreen.svg)](https://github.com/miquido/request-data-collector-guzzle/blob/master/LICENSE)
[![Build](https://travis-ci.org/miquido/request-data-collector-guzzle.svg?branch=master)](https://travis-ci.org/miquido/request-data-collector-guzzle)

## Set up

### GuzzleCollector

This collector is used to collect data about performed Guzzle requests.

```php
'guzzle' => [
	'driver' => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::class,

	'decorate' => [
		'with' => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\Guzzle6ClientDecorator::class,

		'abstracts' => [
			abstract => [
				'type'   => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::TYPE_*,
				'create' => boolean,
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

Every abstract is defined in following way:

```php
abstract => [
	'type'   => \Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::TYPE_*,
	'create' => boolean,
],
```

**abstract** is the name under which instance was registered in container (e.g. `my-guzzle-client` or `\GuzzleHttp\ClientInterface::class`).

**type** defines the type of abstract (see `\Miquido\RequestDataCollector\Collectors\GuzzleCollector\GuzzleCollector::TYPE_*` constants).

When **create** equals `true`, defines to add instance to the container even if it does not exist.
