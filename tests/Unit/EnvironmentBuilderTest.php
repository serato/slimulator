<?php
namespace Serato\Slimulator\Test\Unit;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\Authorization\BasicAuthorization;

/**
 * Unit tests for Serato\Slimulator\EnvironmentBuilder
 */
class EnvironmentBuilderTest extends TestCase
{
    public function testDefaults()
    {
        $env = EnvironmentBuilder::create()->getEnv();
        $this->assertTrue(is_array($env));
        $this->assertEquals($env['REQUEST_METHOD'], 'GET');
    }

    public function testSetRequestMethod()
    {
        $builder = EnvironmentBuilder::create();
        
        $env = $builder->setRequestMethod('POST')->getEnv();
        $this->assertEquals($env['REQUEST_METHOD'], 'POST');
        
        $env = $builder->setRequestMethod('PUT')->getEnv();
        $this->assertEquals($env['REQUEST_METHOD'], 'PUT');
    }

    public function testSetServerProtocol()
    {
        $builder = EnvironmentBuilder::create();

        $env = $builder->setServerProtocol('HTTP/1.2')->getEnv();
        $this->assertEquals($env['SERVER_PROTOCOL'], 'HTTP/1.2');
        
        $env = $builder->setServerProtocol('HTTP/1.1')->getEnv();
        $this->assertEquals($env['SERVER_PROTOCOL'], 'HTTP/1.1');
    }

    public function testSetRemoteIpAddress()
    {
        $builder = EnvironmentBuilder::create();

        $env = $builder->getEnv();
        $this->assertEquals($env['REMOTE_ADDR'], null);

        $env = $builder->setRemoteIpAddress('1.1.1.1')->getEnv();
        $this->assertEquals($env['REMOTE_ADDR'], '1.1.1.1');
    }

    public function testSetXForwardedForIpAddress()
    {
        $builder = EnvironmentBuilder::create();

        $env = $builder->getEnv();
        $this->assertEquals($env['HTTP_X_FORWARDED_FOR'], null);

        $env = $builder->setXForwardedForIpAddress('1.1.1.1')->getEnv();
        $this->assertEquals($env['HTTP_X_FORWARDED_FOR'], '1.1.1.1');
    }

    /**
     * @dataProvider setUriProvider
     */
    public function testSetUri($requestUri, $uri, $queryString, $host, $port, $isHttps)
    {
        $env = EnvironmentBuilder::create()->setUri($requestUri)->getEnv();

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
                EnvironmentBuilder::DEFAULT_HOST,
                EnvironmentBuilder::DEFAULT_PORT,
                'off'
            ],
            [
                'http://mydomain/plain/url',
                '/plain/url',
                '',
                'mydomain',
                EnvironmentBuilder::DEFAULT_PORT,
                'off'
            ],
            [
                'https://mydomain/plain/url',
                '/plain/url',
                '',
                'mydomain',
                EnvironmentBuilder::DEFAULT_PORT,
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
        $builder = EnvironmentBuilder::create();

        $env = $builder->setUri('/plain/url?var1=val1&var2=val2')->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var2=val2');

        $env = $builder->addGetParam('var3', 'val3')->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var2=val2&var3=val3');

        $env = $builder->removeGetParam('var2')->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var3=val3');

        $env = $builder->addGetParams(['var4' =>'val4', 'var5' =>'val5'])->getEnv();
        $this->assertEquals($env['QUERY_STRING'], 'var1=val1&var3=val3&var4=val4&var5=val5');
    }

    public function testSetRemoveHeader()
    {
        // Header doesn't exist by default
        $builder = EnvironmentBuilder::create();

        $env = $builder->getEnv();
        $this->assertTrue(!isset($env['HTTP_ACCEPT']));

        // Add a header value
        $env = $builder->addHeader('Accept', 'text/html')->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html');

        // Add an additional header value
        $env = $builder->addHeader('Accept', 'application/json')->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/json');

        // Add another header value
        $env = $builder->addHeader('Accept', 'application/xml')->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/json, application/xml');

        // Add exisiting header value
        $env = $builder->addHeader('Accept', 'application/json')->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/json, application/xml');

        // Remove existing header value
        $env = $builder->removeHeader('Accept', 'application/json')->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/xml');

        // Remove a non-existent header value
        $env = $builder->removeHeader('Accept', 'nosuchthing')->getEnv();
        $this->assertEquals($env['HTTP_ACCEPT'], 'text/html, application/xml');

        // Remove entire header
        $env = $builder->removeHeader('Accept')->getEnv();
        $this->assertTrue(!isset($env['HTTP_ACCEPT']));
    }

    public function testSetRemoveCookie()
    {
        // No cookies by default
        $builder = EnvironmentBuilder::create();

        $env = $builder->getEnv();
        $this->assertTrue(!isset($env['HTTP_COOKIE']));

        // Add a cookie
        $env = $builder->addCookie('cookie1', 'cookie_val1')->getEnv();
        $this->assertEquals($env['HTTP_COOKIE'], 'cookie1=' . urlencode('cookie_val1'));

        // Add another cookie
        $env = $builder->addCookie('cookie2', 'cookie_val2')->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie2=' . urlencode('cookie_val2')
        );

        // ...and another cookie
        $env = $builder->addCookie('cookie3', 'cookie_val3')->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie2=' . urlencode('cookie_val2') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );

        // Add existing cookie
        $env = $builder->addCookie('cookie2', 'cookie_val2')->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie2=' . urlencode('cookie_val2') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );

        // Remove existing cookie
        $env = $builder->removeCookie('cookie2')->getEnv();
        $this->assertEquals(
            $env['HTTP_COOKIE'],
            'cookie1=' . urlencode('cookie_val1') . '; ' .
            'cookie3=' . urlencode('cookie_val3')
        );

        // Remove non-existent cookie
        $env = $builder->removeCookie('cookie_1000')->getEnv();
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
        
        $auth = BasicAuthorization::create($user_name, $user_pass);
        
        $env = EnvironmentBuilder::create()->setAuthorization($auth)->getEnv();

        $this->assertEquals($env['HTTP_AUTHORIZATION'], $auth->getHeaderValue());
        $this->assertEquals($env['PHP_AUTH_USER'], $user_name);
        $this->assertEquals($env['PHP_AUTH_PW'], $user_pass);
    }
}
