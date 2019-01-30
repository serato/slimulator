# The Slimulator [![Build Status](https://travis-ci.org/serato/slimulator.svg?branch=master)](https://travis-ci.org/serato/slimulator)

[![Latest Stable Version](https://img.shields.io/packagist/v/serato/slimulator.svg)](https://packagist.org/packages/serato/slimulator)

A library to aid with the testing of web applications that use
[Slim](https://www.slimframework.com/) PHP web framework.

## Requirements

* PHP 7.1 or above
* Tested against Slim >= 3.8.x.
* Not currently tested against Slim >= 4.x.x

## Installation

Installation will usually consist of adding the library to a project's `composer.json` file:

```json
{
	"require": {
		"serato/slimulator": "^1.0"
	}
}
```

## Introduction

The Slimulator is intended to aid in the testing of Slim web applications.

It provides an `EnvironmentBuilder` class for programmatially creating a PHP request environment,
as well as modified `Request` and `UploadedFile` classes to work with a request environment created
from the `EnvironmentBuilder` class.

This makes it simpler to create a complete `Request` object for testing purposes along with simplifying
the process of mocking an entire Slim application request execution and inspecting the returned
`Response` object.

## Usage

### Creating a request environment

Use `Serato\Slimulator\EnvironmentBuilder` to programmatically define a request:

```php
use Serato\Slimulator\EnvironmentBuilder;

$envBuilder = EnvironmentBuilder::create()
	->setRequestMethod('GET')
	->setUri('http://my.server/my/uri?var1=val1')
	->addGetParam('var2', 'val2')
	->addHeader('Cache-Control', 'no-cache')
	->addCookie('my_session', 'session_vars');

```

Get an array representation from a `Serato\Slimulator\EnvironmentBuilder` instance
(equivalent to the `$_SERVER` PHP superglobal):

```php
use Serato\Slimulator\EnvironmentBuilder;

$envBuilder = EnvironmentBuilder::create()->setUri('http://my.server/my/uri?var1=val1');

$server = $envBuilder->getEnv();

```

Create a `Slim\Http\Environment` object from a `Serato\Slimulator\EnvironmentBuilder` instance:

```php
use Serato\Slimulator\EnvironmentBuilder;

$envBuilder = EnvironmentBuilder::create()->setUri('http://my.server/my/uri?var1=val1');

$env = $envBuilder->getSlimEnvironment();

```

#### Adding a request entity body to a request environment

Request entity bodies can be added to `PUT ` and `POST` requests. Different `Content-Type`
are supported:

```php
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\RequestBody\UrlEncoded;
use Serato\Slimulator\RequestBody\Multipart;
use Serato\Slimulator\RequestBody\Json;
use Serato\Slimulator\RequestBody\Xml;

// Create a request body using `application/x-www-form-urlencoded` encoding
$body = UrlEncoded::create(['var1' => 'val1', 'var2' => 'val2']);

// Create a request body with an `application/json` content type
$body = Json::create(['var1' => 'val1', 'var2' => 'val2']);
// Can also be created from a JSON string
$body = Json::create('{"var1":"val1","var2":"val2"}');

// Create a request body with an `application/xml` content type
$body = Xml::create('<xml><var1>val1</var1><var2>val2</var2></xml>');

// Create a multipart request body
$body = Multipart::create()
	->addParam('var1', 'val1') // Add a name/value pair
	->addFile('file1', '/my/local/file/path'); // Add a file

// Add the body to a request environment
$envBuilder = EnvironmentBuilder::create()
	->setRequestMethod('POST')
	->setUri('http://my.server/my/uri')
	->setRequestBody($body);

```

#### Adding an authorization scheme to a request environment

There are two convenience classes for adding commonly used authorization schemes
to a request environment:

```php
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\Authorization\BasicAuthorization;
use Serato\Slimulator\Authorization\BearerToken;

// Create a request environment that uses `Basic` authorization
$envBuilder = EnvironmentBuilder::create()
	->setUri('http://my.server/my/uri')
	->setAuthorization(BasicAuthorization::create('myuser', 'mypass'));

// Create a request environment that uses a `Bearer` token
$envBuilder = EnvironmentBuilder::create()
	->setUri('http://my.server/my/uri')
	->setAuthorization(BearerToken::create('mytoken'));

```

### Creating a Request object from an EnvironmentBuilder instance

`Serato\Slimulator\Request` extends `Slim\Http\Request` by adding a static
method that allows it to be created from an `EnvironmentBuilder` instance:

```php
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\Request;

$envBuilder = EnvironmentBuilder::create()->setUri('http://my.server/my/uri');
$request = Request::createFromEnvironmentBuilder($envBuilder);

```

### Mocking a Slim application request execution

Once you have created a request environment using `EnvironmentBuilder` it's a simple
process to simulate a complete Slim application request execution:


```php
use Slim\App;
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\Request;

// Create the app...
$app = new App();
// ...and get the DI container
$container = $app->getContainer();

// Create an EnvironmentBuilder in the container
$container['environmentBuilder'] = function () {
	return EnvironmentBuilder::create()->setUri('http://my.server/my/uri');
}

// Replace the default `environment` in the container with our constructed
// environment created out of the EnvironmentBuilder instance
$container['environment'] = function ($c) {
	return $c->get('environmentBuilder')->getSlimEnvironment();
};

// And do the same for the container's default `request` object
$container['request'] = function ($c) {
	return Request::createFromEnvironmentBuilder(
		$c->get('environmentBuilder')
	);
};

// Add routes, middleware, handlers etc
// ...

// Get the `response` object by calling App::run with the `$silent` argument set to `true`.
$response = $app->run(true);

```
