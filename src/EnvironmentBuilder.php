<?php
declare(strict_types=1);

namespace Serato\Slimulator;

use Slim\Http\Environment;
use Serato\Slimulator\Authorization\HttpAuthorizationInterface;
use Serato\Slimulator\RequestBody\RequestBodyInterface;
use Exception;

/**
 * Provides an abstracted, fluent interface for creating a PHP request environment.
 */
class EnvironmentBuilder
{
    const DEFAULT_USER_AGENT = 'Slimulator/1.0.0';
    const DEFAULT_IS_HTTPS = false;
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 80;
    const DEFAULT_REQUEST_URI = '';

    /**
     * HTTP request method
     *
     * @var string
     */
    private $method = 'GET';

    /**
     * Server protocol
     *
     * @var string
     */
    private $serverProtocol = 'HTTP/1.1';

    /**
     * Connection over HTTPS
     *
     * @var bool
     */
    private $https;

    /**
     * Host name
     *
     * @var string
     */
    private $httpHost;

    /**
     * Server port
     *
     * @var int
     */
    private $serverPort;

    /**
     * Remote IP address
     *
     * @var string
     */
    private $remoteIpAddress;

    /**
     * Forwarded IP address
     *
     * @var string
     */
    private $xForwardedFor;

    /**
     * Request URI
     *
     * @var string
     */
    private $requestUri;

    /**
     * HTTP headers
     *
     * @var Array<string, mixed>
     */
    private $headers = [];

    /**
     * Content type
     *
     * @var string
     */
    private $contentType;   # Determined by header

    /**
     * Content length
     *
     * @var int
     */
    // TODO: Should be set by size of request body
    private $contentLength;

    /**
     * GET parameters
     *
     * @var Array<mixed, mixed>
     */
    private $getParams = [];

    /**
     * Cookies
     *
     * @var Array<mixed, mixed>
     */
    private $cookies = [];

    /**
     * HTTP Authorization scheme
     *
     * @var HttpAuthorizationInterface
     */
    private $authorization;

    /**
     * The body of the request
     *
     * @var RequestBodyInterface
     */
    private $requestBody;

    /**
     * Create a new EnvironmentBuilder
     *
     * @return self
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Constructs the object
     *
     * @return void
     */
    public function __construct()
    {
        // Set some defaults
        $this
            ->setUriDefaults()
            ->addHeader('User-Agent', self::DEFAULT_USER_AGENT)
            ->addHeader('Accept-Encoding', 'gzip')
            ->addHeader('Accept-Encoding', 'deflate')
            ->addHeader('Cache-Control', 'no-cache')
            ->addHeader('Connection', 'keep-alive');
    }

    /**
     * Sets the HTTP request method.
     *
     * Can be one of any supported HTTP methods - `GET`, `HEAD`, `POST`, `PUT`,
     * `PATCH`, `DELETE`, `CONNECT`, `OPTIONS`, `TRACE`.
     *
     * Or can be any non-valid method name to test error scenarios.
     *
     * Defaults to `GET` if not specified.
     *
     * @returns self
     */
    public function setRequestMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Sets the HTTP protocol of the request.
     *
     * Defaults to `HTTP/1.1` if not specified.
     *
     * @returns self
     */
    public function setServerProtocol(string $serverProtocol): self
    {
        $this->serverProtocol = $serverProtocol;
        return $this;
    }

    /**
     * Sets the remote IP address of the request.
     *
     * @returns self
     */
    public function setRemoteIpAddress(string $ipAddress): self
    {
        $this->remoteIpAddress = $ipAddress;
        return $this;
    }

    /**
     * Sets the forwarded IP address of the request.
     *
     * @returns self
     */
    public function setXForwardedForIpAddress(string $ipAddress): self
    {
        $this->xForwardedFor = $ipAddress;
        return $this;
    }

    /**
     * Sets the URI of the request.
     * Can include any combination of protocol, server name, port, URI and GET vars.
     *
     * Examples:
     *
     * - `/plain/uri`
     * - `/plain/uri?var1=val1`
     * - `http://myserver/uri`
     * - `https://myserver/uri?var1=val1&var2=val2`
     * - `http://myserver:8080/uri`
     *
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->setUriDefaults();
        $parsedUri = parse_url($uri);
        if ($parsedUri === false) {
            throw new Exception('Invalid URI `' . $uri . '`');
        }
        foreach ($parsedUri as $k => $v) {
            $v = (string)$v;
            switch ($k) {
                case 'scheme':
                    if (strtolower($v) == 'https') {
                        $this->https = true;
                    }
                    break;
                case 'host':
                    $this->httpHost = $v;
                    break;
                case 'port':
                    $this->serverPort = (int)$v;
                    break;
                case 'path':
                    $this->requestUri = $v;
                    break;
                case 'query':
                    parse_str($v, $result);
                    $this->addGetParams($result);
                    break;
            }
        }
        return $this;
    }

    /**
     * Adds a single GET parameter.
     *
     * @param string    $name   Parameter name
     * @param mixed     $value  Parameter value
     *
     * @return self
     */
    public function addGetParam(string $name, $value): self
    {
        $this->getParams[$name] = $value;
        return $this;
    }

    /**
     * Adds multiple GET parameters.
     * eg. `['param1' => 'val1', 'param2' => 'val2']`.
     *
     * @param Array<mixed, mixed> $params     Name/value array of parameters
     *
     * @return self
     */
    public function addGetParams(array $params): self
    {
        foreach ($params as $k => $v) {
            $this->addGetParam((string)$k, $v);
        }
        return $this;
    }

    /**
     * Removes a GET parameter.
     *
     * @return self
     */
    public function removeGetParam(string $name): self
    {
        unset($this->getParams[$name]);
        return $this;
    }

    /**
     * Adds a single value for a named HTTP request header.
     *
     * Can be called multiple times to set multiple comma separated values for a
     * single header.
     *
     * @param string $name      Header name
     * @param string $value     Header value
     *
     * @return self
     */
    public function addHeader(string $name, string $value): self
    {
        // Note currently doesn't support Quality Value syntax
        // https://developer.mozilla.org/en-US/docs/Glossary/Quality_Values
        // Seems like overkill for now.

        if (!isset($this->headers[$name])) {
            $this->headers[$name] = [];
        }
        if (!in_array($value, $this->headers[$name])) {
            $this->headers[$name][] = $value;
        }
        return $this;
    }

    /**
     * Removes HTTP request headers or values.
     *
     * If `$value` is provided `$value` is removed from header named `$name`.
     *
     * If `$value` is not provided entire header named `$name` is removed.
     *
     * @param string $name Header name
     * @param string|null $value Header value
     *
     * @return self
     */
    public function removeHeader(string $name, string $value = null): self
    {
        if (isset($this->headers[$name])) {
            if ($value === null) {
                unset($this->headers[$name]);
            } else {
                if (in_array($value, $this->headers[$name])) {
                    $this->headers[$name] = array_filter(
                        $this->headers[$name],
                        function ($v) use ($value) {
                            return $v !== $value;
                        }
                    );
                }
            }
        }
        return $this;
    }

    /**
     * Adds a cookie.
     *
     * @param string $name      Cookie name
     * @param string $value     Cookie value
     *
     * @return self
     */
    public function addCookie(string $name, string $value): self
    {
        $this->cookies[$name] = $value;
        return $this;
    }

    /**
     * Removes a cookie.
     *
     * @return self
     */
    public function removeCookie(string $name): self
    {
        unset($this->cookies[$name]);
        return $this;
    }

    /**
     * Sets an HTTP authorization scheme.
     *
     * @param HttpAuthorizationInterface $authorization Authorization scheme
     *
     * @return self
     */
    public function setAuthorization(HttpAuthorizationInterface $authorization): self
    {
        $this->authorization = $authorization;
        return $this;
    }

    /**
     * Removes the current HTTP authorization scheme.
     *
     * @return self
     */
    public function removeAuthorization(): self
    {
        unset($this->authorization);
        return $this;
    }

    /**
     * Sets the request body.
     *
     * @param RequestBodyInterface $requestBody Request body
     *
     * @return self
     */
    public function setRequestBody(RequestBodyInterface $requestBody): self
    {
        $this->requestBody = $requestBody;
        return $this;
    }

    /**
     * Returns the request body.
     *
     * @return RequestBodyInterface|null
     */
    public function getRequestBody(): ?RequestBodyInterface
    {
        return $this->requestBody;
    }

    /**
     * Removes the current request body.
     *
     * @return self
     */
    public function removeRequestBody(): self
    {
        unset($this->requestBody);
        return $this;
    }

    /**
     * Returns the entire environment as an array.
     *
     * @return Array<string, mixed>
     */
    public function getEnv(): array
    {
        $getVars = http_build_query($this->getParams);

        $vars = [
            'SERVER_PROTOCOL'       => $this->serverProtocol,
            'HTTPS'                 => $this->https ? 'on' : 'off',
            'SERVER_PORT'           => $this->serverPort,
            'HTTP_HOST'             => $this->httpHost,
            'REQUEST_METHOD'        => $this->method,
            'REQUEST_URI'           => $this->requestUri . ($getVars == '' ? '' : '?' . $getVars),
            'QUERY_STRING'          => $getVars,
            'REQUEST_TIME_FLOAT'    => microtime(true),
            'REQUEST_TIME'          => time(),
            'REMOTE_ADDR'           => $this->remoteIpAddress,
            'HTTP_X_FORWARDED_FOR'  => $this->xForwardedFor
        ];

        if ($this->authorization !== null) {
            $vars = array_merge(
                $vars,
                ['HTTP_AUTHORIZATION' => $this->authorization->getHeaderValue()],
                $this->authorization->getPhpEnvVars()
            );
        }

        if (count($this->cookies) > 0) {
            $vars['HTTP_COOKIE'] = $this->getCookieHeaderValue();
        }

        if ($this->getRequestBody() !== null) {
            $this->addHeader('Content-Type', $this->getRequestBody()->getContentType());
            $this->contentLength = $this->getRequestBody()->getContentLength();
        }

        foreach ($this->headers as $name => $value) {
            $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
            $headerValue = implode(', ', $value);
            $vars[$headerName] = $headerValue;
            if (strtolower($name) == 'content-type') {
                $this->contentType = $headerValue;
            }
        }

        return array_merge(
            $vars,
            [
                'CONTENT_TYPE'      => $this->contentType,
                'CONTENT_LENGTH'    => $this->contentLength
            ]
        );
    }

    /**
     * Creates a new Slim Environment from the current EnvironmentBuilder state.
     *
     * @return Environment
     */
    public function getSlimEnvironment(): Environment
    {
        return Environment::mock($this->getEnv());
    }

    private function getCookieHeaderValue(): string
    {
        $cookies = [];
        foreach ($this->cookies as $name => $value) {
            $cookies[] = $name . '=' . urlencode($value);
        }
        return implode('; ', $cookies);
    }

    private function setUriDefaults(): self
    {
        $this->https = self::DEFAULT_IS_HTTPS;
        $this->httpHost = self::DEFAULT_HOST;
        $this->serverPort = self::DEFAULT_PORT;
        $this->requestUri = self::DEFAULT_REQUEST_URI;
        return $this;
    }
}
