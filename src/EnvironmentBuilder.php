<?php
namespace Serato\Slimulator;

use Serato\Slimulator\Authorization\HttpAuthorizationInterface;

/**
 * Creates a PHP request environment
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
     * Request URI
     *
     * @var string
     */
    private $requestUri;

    /**
     * HTTP headers
     *
     * @var array
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
    private $contentLength; # Determined by header

    /**
     * GET parameters
     *
     * @var array
     */
    private $getParams = [];

    /**
     * Cookies
     *
     * @var array
     */
    private $cookies = [];

    /**
     * HTTP Authorization scheme
     *
     * @var HttpAuthorizationInterface
     */
    private $authorization;

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
            ->setHeader('User-Agent', self::DEFAULT_USER_AGENT)
            ->setHeader('Accept-Encoding', 'gzip')
            ->setHeader('Accept-Encoding', 'deflate')
            ->setHeader('Cache-Control', 'no-cache')
            ->setHeader('Connection', 'keep-alive');
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
        foreach (parse_url($uri) as $k => $v) {
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
                    $this->setGetParams($result);
                    break;
            }
        }
        return $this;
    }

    /**
     * Sets a single GET parameter name and value
     *
     * @param string    $name   Parameter name
     * @param mixed     $value  Parameter value
     *
     * @return self
     */
    public function setGetParam(string $name, $value): self
    {
        $this->getParams[$name] = $value;
        return $this;
    }

    /**
     * Sets multiple GET parameters from a single name/value array.
     * eg. `['param1' => 'val1', 'param2' => 'val2']`.
     *
     * @param array $params     Name/value array of parameters
     *
     * @return self
     */
    public function setGetParams(array $params): self
    {
        foreach ($params as $k => $v) {
            $this->setGetParam($k, $v);
        }
        return $this;
    }

    /**
     * Removes a named GET parameter.
     *
     * @return self
     */
    public function removeGetParam(string $name): self
    {
        unset($this->getParams[$name]);
        return $this;
    }

    /**
     * Sets a single value for a named HTTP request header.
     *
     * Can be called multiple times to set multiple comma separated values for a
     * single header.
     *
     * @param string $name      Header name
     * @param string $value     Header value
     *
     * @return self
     */
    public function setHeader(string $name, string $value): self
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
     * @param string $name      Header name
     * @param string $value     Header value
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
     * Sets a cookie.
     *
     * @param string $name      Cookie name
     * @param string $value     Cookie value
     *
     * @return self
     */
    public function setCookie(string $name, string $value): self
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
     * Sets an HTTP authorization scheme
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
     * Removes a previously defined HTTP authorization scheme
     *
     * @return self
     */
    public function removeAuthorization(): self
    {
        $this->authorization = null;
        return $this;
    }

    /**
     * Returns the entire environment as an array.
     *
     * @return array
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
            'REQUEST_TIME'          => time()
        ];

        if ($this->authorization) {
            $vars = array_merge(
                $vars,
                ['HTTP_AUTHORIZATION' => $this->authorization->getHeaderValue()],
                $this->authorization->getPhpEnvVars()
            );
        }

        if (count($this->cookies) > 0) {
            $vars['HTTP_COOKIE'] = $this->getCookieHeaderValue();
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
