<?php
declare(strict_types=1);

namespace Serato\Slimulator\Test\Integration;

use PHPUnit\Framework\TestCase;
use Slim\App as SlimApp;
use Psr\Http\Message\ResponseInterface;
use Serato\Slimulator\Request;
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\RequestBody\Json;
use Serato\Slimulator\RequestBody\Multipart;
use Serato\Slimulator\RequestBody\UrlEncoded;
use Serato\Slimulator\Authorization\BasicAuthorization;
use Serato\Slimulator\Authorization\BearerToken;

/**
 * Unit tests for Serato\Slimulator\Request
 */
class IntegrationTest extends TestCase
{
    const HTML_URI = '/html';
    const JSON_URI = '/json';
    const NOT_FOUND_URI = '/404uri';
    const AUTH_USER = 'auth.user';
    const USER_PASS = 'auth-user-pass';
    const BEARER_TOKEN = 'my123token456';
    const REQUEST_PARAMS = ['var1' => 'My Value 1', 'var2' => 2, 'var3' => '333 value'];

    public function testNotFoundUriAcceptHtml()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::NOT_FOUND_URI);
        };

        $response = $this->bootstrapSlimApp($callable);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertRegExp('/text\/html/', $response->getHeader('Content-Type')[0]);
    }

    /**
     * Test setting the 'Accepts' header to 'application/json'
     * Easiest way to verfiy this is hit a 404 and lean on the default Slim
     * NotFound handler to set the correct content type.
     */
    public function testNotFoundUriAcceptJson()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::NOT_FOUND_URI)
                ->addHeader('Accept', 'application/json');
        };

        $response = $this->bootstrapSlimApp($callable);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertRegExp('/application\/json/', $response->getHeader('Content-Type')[0]);
    }

    /**
     * As above but with 'Accepts' set to 'text/xml'
     */
    public function testNotFoundUriAcceptXml()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::NOT_FOUND_URI)
                ->addHeader('Accept', 'text/xml');
        };

        $response = $this->bootstrapSlimApp($callable);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertRegExp('/text\/xml/', $response->getHeader('Content-Type')[0]);
    }

    public function testValidUriAcceptHtml()
    {
        $callable = function () {
            return EnvironmentBuilder::create()->setUri(self::HTML_URI);
        };

        $response = $this->bootstrapSlimApp($callable);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('/text\/html/', $response->getHeader('Content-Type')[0]);
    }

    public function testGetParams()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->addGetParams(self::REQUEST_PARAMS);
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body['query'], self::REQUEST_PARAMS);
    }

    public function testUrlEncodedEntityBody()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->setRequestMethod('POST')
                ->setRequestBody(UrlEncoded::create(self::REQUEST_PARAMS));
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body['body'], self::REQUEST_PARAMS);
    }

    public function testMultipartEntityBody()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->setRequestMethod('POST')
                ->setRequestBody(Multipart::create(self::REQUEST_PARAMS));
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body['body'], self::REQUEST_PARAMS);
    }

    public function testMultipartEntityBodyWithFiles()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->setRequestMethod('POST')
                ->setRequestBody(
                    Multipart::create(
                        self::REQUEST_PARAMS,
                        [
                            'file1' => $this->getUploadFilePath(1),
                            'file2' => $this->getUploadFilePath(2)
                        ]
                    )
                );
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body['body'], self::REQUEST_PARAMS);
        $this->assertEquals(
            $body['files']['file1'],
            file_get_contents($this->getUploadFilePath(1))
        );
        $this->assertEquals(
            $body['files']['file2'],
            file_get_contents($this->getUploadFilePath(2))
        );
    }

    public function testJsonEntityBody()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->setRequestMethod('POST')
                ->setRequestBody(Json::create(self::REQUEST_PARAMS));
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body['body'], self::REQUEST_PARAMS);
    }

    public function testBasicAuth()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->setAuthorization(
                    BasicAuthorization::create(self::AUTH_USER, self::USER_PASS)
                );
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body['user'], self::AUTH_USER . ':' . self::USER_PASS);
        $this->assertRegExp('/Basic/', $body['auth'][0]);
    }

    public function testBearerTokenAuth()
    {
        $callable = function () {
            return EnvironmentBuilder::create()
                ->setUri(self::JSON_URI)
                ->setAuthorization(
                    BearerToken::create(self::BEARER_TOKEN)
                );
        };

        $response = $this->bootstrapSlimApp($callable);
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('/' . self::BEARER_TOKEN . '/', $body['auth'][0]);
    }

    /**
     * Bootstrap a Slim application, run it and return a ResponseInterface.
     *
     * @param callable  $env    A callable that returns an EnvironmentBuilder instance
     *
     * @return ResponseInterface
     */
    protected function bootstrapSlimApp(callable $env): ResponseInterface
    {
        $app = new SlimApp();
        $container = $app->getContainer();

        // Add our callable to the container
        $container['environmentBuilder'] = $env;

        // Replace the default `environment` in the container with our constructed
        // environment created out of the EnvironmentBuilder instance
        $container['environment'] = function ($c) {
            return $c->get('environmentBuilder')->getSlimEnvironment();
        };
        // And do the same for the container's `request` object
        $container['request'] = function ($c) {
            return Request::createFromEnvironmentBuilder(
                $c->get('environmentBuilder')
            );
        };

        // Add routes
        $app->any(self::HTML_URI, function ($request, $response, $args) {
        });
        
        $app->any(self::JSON_URI, function ($request, $response, $args) {
            $files = [];

            foreach ($request->getUploadedFiles() as $name => $file) {
                $files[$name] = (string)$file->getStream();
            }

            return $response
                ->withHeader('Content-type', 'application/json')
                ->write(json_encode(
                    [
                        'args'      => $args,
                        'query'     => $request->getQueryParams(),
                        'body'      => $request->getParsedBody(),
                        'files'     => $files,
                        'user'      => $request->getUri()->getUserInfo(),
                        'auth'      => $request->getHeader('Authorization'),
                        'headers'   => $request->getHeaders()
                    ],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
        });

        return $app->run(true);
    }

    protected function getUploadFilePath($num)
    {
        return __DIR__ . '/../resources/upload' . $num . '.txt';
    }
}
