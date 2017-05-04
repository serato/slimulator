<?php
namespace Serato\Slimulator\Test;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestEnvironment;
use Serato\Slimulator\HttpBasicAuthorization;

/**
 * Unit tests for Serato\Slimulator\RequestEnvironment
 */
class RequestEnvironmentTest extends TestCase
{
    public function testDefaults()
    {
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment->getEnv();
        $this->assertTrue(is_array($env));
        $this->assertEquals($env['REQUEST_METHOD'], 'GET');
    }

    public function testSetRequestMethod()
    {
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->setRequestMethod('POST')
                    ->getEnv();
        $this->assertEquals($env['REQUEST_METHOD'], 'POST');
        $env = $requestEnvironment
                    ->setRequestMethod('PUT')
                    ->getEnv();
        $this->assertEquals($env['REQUEST_METHOD'], 'PUT');
    }

    public function testSetServerProtocol()
    {
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->setServerProtocol('HTTP/1.2')
                    ->getEnv();
        $this->assertEquals($env['SERVER_PROTOCOL'], 'HTTP/1.2');
        $env = $requestEnvironment
                    ->setServerProtocol('HTTP/1.1')
                    ->getEnv();
        $this->assertEquals($env['SERVER_PROTOCOL'], 'HTTP/1.1');
    }

    /**
     * @dataProvider setUriProvider
     */
    public function testSetUri($requestUri, $uri, $queryString, $host, $port, $isHttps)
    {
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->setUri($requestUri)
                    ->getEnv();
        $this->assertEquals($env['REQUEST_URI'], $uri);
        $this->assertEquals($env['QUERY_STRING'], $queryString);
        $this->assertEquals($env['HTTP_HOST'], $host);
        $this->assertEquals($env['SERVER_PORT'], $port);
        $this->assertEquals($env['HTTPS'], $isHttps);
    }

    public function setUriProvider()
    {
        return [
            [
                '/plain/url',
                '/plain/url',
                '',
                RequestEnvironment::DEFAULT_HOST,
                RequestEnvironment::DEFAULT_PORT,
                'off'
            ],
            [
                'http://mydomain/plain/url',
                '/plain/url',
                '',
                'mydomain',
                RequestEnvironment::DEFAULT_PORT,
                'off'
            ],
            [
                'https://mydomain/plain/url',
                '/plain/url',
                '',
                'mydomain',
                RequestEnvironment::DEFAULT_PORT,
                'on'
            ],
            [
                'http://mydomain:8080/plain/url',
                '/plain/url',
                '',
                'mydomain',
                8080,
                'off'
            ],
            [
                'http://mydomain:8080/plain/url?var1=val1&var2=val2',
                '/plain/url?var1=val1&var2=val2',
                'var1=val1&var2=val2',
                'mydomain',
                8080,
                'off'
            ]
        ];
    }

    public function testSetRemoveGetParams()
    {
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->setUri('/plain/url?var1=val1&var2=val2')
                    ->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var2=val2');

        $env = $requestEnvironment
                    ->setGetParam('var3', 'val3')
                    ->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var2=val2&var3=val3');

        $env = $requestEnvironment
                    ->removeGetParam('var2')
                    ->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var3=val3');

        $env = $requestEnvironment
                    ->setGetParams(['var4' =>'val4', 'var5' =>'val5'])
                    ->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var3=val3&var4=val4&var5=val5');
    }

    public function testSetRemoveHeader()
    {
        // Header doesn't exist by default
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->getEnv();
        $this->assertTrue(!isset($env['HTTP_ACCEPT']));

        // Add a header value
        $env = $requestEnvironment
                    ->setHeader('Accept', 'text/html')
                    ->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html');

        // Add an additional header value
        $env = $requestEnvironment
                    ->setHeader('Accept', 'application/json')
                    ->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/json');

        // Add another header value
        $env = $requestEnvironment
                    ->setHeader('Accept', 'application/xml')
                    ->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/json, application/xml');

        // Add exisiting header value
        $env = $requestEnvironment
                    ->setHeader('Accept', 'application/json')
                    ->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/json, application/xml');

        // Remove existing header value
        $env = $requestEnvironment
                    ->removeHeader('Accept', 'application/json')
                    ->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/xml');

        // Remove a non-existent header value
        $env = $requestEnvironment
                    ->removeHeader('Accept', 'nosuchthing')
                    ->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/xml');

        // Remove entire header
        $env = $requestEnvironment
                    ->removeHeader('Accept')
                    ->getEnv();
        $this->assertTrue(!isset($env['HTTP_ACCEPT']));
    }

    public function testSetRemoveCookie()
    {
        // No cookies by default
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->getEnv();
        $this->assertTrue(!isset($env['HTTP_COOKIE']));

        // Add a cookie
        $env = $requestEnvironment
                    ->setCookie('cookie1', 'cookie_val1')
                    ->getEnv();
        $this->assertEquals($env['HTTP_COOKIE'], 'cookie1=' . urlencode('cookie_val1'));

        // Add another cookie
        $env = $requestEnvironment
                    ->setCookie('cookie2', 'cookie_val2')
                    ->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie2=' . urlencode('cookie_val2')
        );

        // ...and another cookie
        $env = $requestEnvironment
                    ->setCookie('cookie3', 'cookie_val3')
                    ->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie2=' . urlencode('cookie_val2') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );

        // Add existing cookie
        $env = $requestEnvironment
                    ->setCookie('cookie2', 'cookie_val2')
                    ->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie2=' . urlencode('cookie_val2') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );

        // Remove existing cookie
        $env = $requestEnvironment
                    ->removeCookie('cookie2')
                    ->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );

        // Remove non-existent cookie
        $env = $requestEnvironment
                    ->removeCookie('cookie_1000')
                    ->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );
    }

    public function testBasicAuth()
    {
        $user_name = 'myuser';
        $user_pass = 'mypass';
        
        $auth = new HttpBasicAuthorization($user_name, $user_pass);
        $requestEnvironment = new RequestEnvironment();
        $env = $requestEnvironment
                    ->setAuthorization($auth)
                    ->getEnv();

        $this->assertEquals($env['HTTP_AUTHORIZATION'], $auth->getHeaderValue());
        $this->assertEquals($env['PHP_AUTH_USER'], $user_name);
        $this->assertEquals($env['PHP_AUTH_PW'], $user_pass);
    }
}
