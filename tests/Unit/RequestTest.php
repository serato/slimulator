<?php
namespace Serato\Slimulator\Test\Unit;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\Request;
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\Authorization\BasicAuthorization;
use Serato\Slimulator\Authorization\BearerToken;
use Serato\Slimulator\RequestBody\Json;
use Serato\Slimulator\RequestBody\Multipart;
use Serato\Slimulator\RequestBody\UrlEncoded;
use Serato\Slimulator\RequestBody\Xml;

/**
 * Unit tests for Serato\Slimulator\Request
 */
class RequestTest extends TestCase
{
    const CUSTOM_HEADER_NAME = 'My-Custom-Header';
    const ENTITY_BODY_DATA = ['var1' => 'Value 1', 'var2' => 2, 'var3' => 'val_3'];
    /**
     *
     * @param string    $envRequestMethod           Request method as provided to EnvironmentBuilder
     * @param string    $envRequestUri              Full URI as provided to EnvironmentBuilder
     * @param array     $envGetParams               GET params as provided to EnvironmentBuilder
     * @param array     $envCookies                 Array of cookies provided to EnvironmentBuilder
     * @param array     $envCustomHeaderValues      Array of values for custom header provided to EnvironmentBuilder
     * @param string    $requestUriPath             URI path as parsed by Request object
     * @param array     $requestGetParams           GET params are parsed by Request object
     * @param strin     $requestCustomHeaderValue   Custom header value parsed by Request object
     *
     * @dataProvider simpleRequestsProvider
     */
    public function testRequestsNoEntityBody(
        string $envRequestMethod,
        string $envRequestUri,
        array $envGetParams,
        array $envCookies,
        array $envCustomHeaderValues,
        string $requestUriPath,
        array $requestGetParams,
        string $requestCustomHeaderValue
    ) {
        $env = EnvironmentBuilder::create()
            ->setRequestMethod($envRequestMethod)
            ->setUri($envRequestUri)
            ->addGetParams($envGetParams);

        foreach ($envCookies as $k => $v) {
            $env = $env->addCookie($k, $v);
        }

        foreach ($envCustomHeaderValues as $val) {
            $env = $env->addHeader(self::CUSTOM_HEADER_NAME, $val);
        }

        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );

        $this->assertEquals($request->getMethod(), $envRequestMethod);
        $this->assertEquals($request->getUri()->getPath(), $requestUriPath);
        $this->assertEquals($request->getQueryParams(), $requestGetParams);
        $this->assertEquals($request->getCookieParams(), $envCookies);
        if ($request->hasHeader(self::CUSTOM_HEADER_NAME)) {
            $this->assertEquals(
                $request->getHeader(self::CUSTOM_HEADER_NAME)[0],
                $requestCustomHeaderValue
            );
        }
    }

    public function testRequestWithBasicAuth()
    {
        $user = 'my_user';
        $pass = 'passs';
        
        $env = EnvironmentBuilder::create()
            ->setUri('https://my.server.com/level1/level2')
            ->setAuthorization(
                BasicAuthorization::create($user, $pass)
            );
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertRegExp(
            '/Basic/',
            $request->getHeader('Authorization')[0]
        );
        $this->assertEquals(
            $request->getUri()->getUserInfo(),
            $user . ':' . $pass
        );
    }

    public function testRequestWithBearerToken()
    {
        $env = EnvironmentBuilder::create()
            ->setUri('https://my.server.com/level1/level2')
            ->setAuthorization(
                BearerToken::create('my_big_log_token')
            );
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertRegExp(
            '/Bearer/',
            $request->getHeader('Authorization')[0]
        );
    }

    /**
     * Note: Slim 3.8.* added a Slim\Exception\InvalidMethodException
     * exception class which extends InvalidArgumentException. But use
     * InvalidArgumentException to maintain compatibility with older Slim
     * versions.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHTTPMethod()
    {
        $env = EnvironmentBuilder::create()
            ->setRequestMethod('NOT_A_REAL_HTTP_METHOD')
            ->setUri('https://my.server.com/level1/level2');
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
    }

    public function testRequestJsonEntityBody()
    {
        $env = EnvironmentBuilder::create()
            ->setRequestMethod('POST')
            ->setUri('https://my.server.com/level1/level2')
            ->setRequestBody(Json::create(self::ENTITY_BODY_DATA));
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
        $this->assertEquals($request->getParsedBody(), self::ENTITY_BODY_DATA);
    }

    public function testRequestUrlEncodedEntityBody()
    {
        $env = EnvironmentBuilder::create()
            ->setRequestMethod('POST')
            ->setUri('https://my.server.com/level1/level2')
            ->setRequestBody(UrlEncoded::create(self::ENTITY_BODY_DATA));
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
        $this->assertEquals($request->getParsedBody(), self::ENTITY_BODY_DATA);
    }

    public function testRequestMultipartEntityBody()
    {
        $env = EnvironmentBuilder::create()
            ->setRequestMethod('POST')
            ->setUri('https://my.server.com/level1/level2')
            ->setRequestBody(Multipart::create(self::ENTITY_BODY_DATA));
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
        $this->assertEquals($request->getParsedBody(), self::ENTITY_BODY_DATA);
    }

    public function testRequestMultipartEntityBodyWithFiles()
    {
        $filepath1 = __DIR__ . '/../resources/upload1.txt';
        $filepath2 = __DIR__ . '/../resources/upload2.txt';
        
        $body = Multipart::create(self::ENTITY_BODY_DATA);
        
        $body->addFile('file1', $filepath1);
        $body->addFile('file2', $filepath2);

        $env = EnvironmentBuilder::create()
            ->setRequestMethod('POST')
            ->setUri('https://my.server.com/level1/level2')
            ->setRequestBody($body);
        $request = Request::createFromEnvironmentBuilder(
            $env->getSlimEnvironment(),
            $env
        );
        $this->assertEquals($request->getParsedBody(), self::ENTITY_BODY_DATA);
        
        $this->assertEquals(
            (string)$request->getUploadedFiles()['file1']->getStream(),
            file_get_contents($filepath1)
        );
        
        $this->assertEquals(
            (string)$request->getUploadedFiles()['file2']->getStream(),
            file_get_contents($filepath2)
        );
    }

    public function simpleRequestsProvider()
    {
        return [
            [
                'GET',
                'https://my.server.com/level1/level2',
                ['var1' => 'val1', 'var2' => 'val2'],
                [
                    'cookie_number_1' => 'cookie_number_1_value',
                    'cookie_number_2' => 'cookie_number_2_value'
                ],
                [],
                '/level1/level2',
                ['var1' => 'val1', 'var2' => 'val2'],
                ''
            ],
            [
                'GET',
                '/level1/level2',
                ['var1' => 'val1', 'var2' => 'val2'],
                [],
                ['header_val_1'],
                '/level1/level2',
                ['var1' => 'val1', 'var2' => 'val2'],
                'header_val_1'
            ],
            [
                'GET',
                '/',
                [],
                [],
                [],
                '/',
                [],
                ''
            ],
            [
                'GET',
                'https://my.server.com',
                ['var1' => 'val1', 'var2' => 'val2'],
                [
                    'cookie_number_1' => 'cookie_number_1_value',
                    'cookie_number_2' => 'cookie_number_2_value'
                ],
                [],
                '/',
                ['var1' => 'val1', 'var2' => 'val2'],
                ''
            ],
            [
                'GET',
                'https://my.server.com/level1/level2?var1=val1',
                ['var2' => 'val2', 'var3' => 'val3'],
                [],
                ['header_val_1', 'header_val_2'],
                '/level1/level2',
                ['var1' => 'val1', 'var2' => 'val2', 'var3' => 'val3'],
                'header_val_1, header_val_2'
            ],
            [
                'PUT',
                'https://my.server.com/level1/level2/level3/level4',
                [],
                [],
                [],
                '/level1/level2/level3/level4',
                [],
                ''
            ],
            [
                'DELETE',
                'https://my.server.com/level1/level2/level3/level4',
                [],
                ['cookie_number_1' => 'cookie_number_1_value'],
                ['header_val_1', 'header_val_2', 'header_val_3'],
                '/level1/level2/level3/level4',
                [],
                'header_val_1, header_val_2, header_val_3'
            ]
        ];
    }
}
