<?php
namespace Serato\Slimulator;

use Serato\Slimulator\HttpAuthorizationInterface;

class RequestEnvironment
{
    const DEFAULT_USER_AGENT = 'Slimulator/1.0.0';
    const DEFAULT_IS_HTTPS = false;
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 80;
    const DEFAULT_REQUEST_URI = '';

    // Done
    private $method = 'GET';
    private $serverProtocol = 'HTTP/1.1';
    // Done. Passed in via setUri
    private $https; // = false;
    private $httpHost; // = 'localhost';
    private $serverPort; // = 80;
    private $requestUri;// = '';
    // Done
    private $headers = [];
    // Done
    private $contentType;   # Determined by header
    // TODO: Should be set by size of request body
    private $contentLength; # Determined by header
    // Done
    private $getParams = [];
    private $cookies = [];
    private $authorization;

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

    public function setRequestMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function setServerProtocol(string $serverProtocol): self
    {
        $this->serverProtocol = $serverProtocol;
        return $this;
    }

    public function setUri(string $uri): self
    {
        $this->setUriDefaults();
        foreach (parse_url($uri) as $k => $v)
        {
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

    public function setGetParams(array $params): self
    {
        foreach ($params as $k => $v) {
            $this->setGetParam($k, $v);
        }
        return $this;
    }

    public function setGetParam(string $name, $value): self
    {
        $this->getParams[$name] = $value;
        return $this;
    }

    public function removeGetParam(string $name): self
    {
        unset($this->getParams[$name]);
        return $this;
    }

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

    public function removeHeader(string $name, string $value = null): self
    {
        if (isset($this->headers[$name])) {
            if ($value === null) {
                unset($this->headers[$name]);
            } else {
                if (in_array($value, $this->headers[$name])) {
                    $this->headers[$name] = array_filter(
                        $this->headers[$name],
                        function($v) use ($value) {
                            return $v !== $value;
                        }
                    );
                }
            }
        }
        return $this;
    }

    public function setCookie(string $name, string $value): self
    {
        $this->cookies[$name] = $value;
        return $this;
    }

    public function removeCookie(string $name): self
    {
        unset($this->cookies[$name]);
        return $this;
    }

    public function setAuthorization(HttpAuthorizationInterface $authorization): self
    {
        $this->authorization = $authorization;
        return $this;
    }

    public function removeAuthorization(): self
    {
        $this->authorization = null;
        return $this;
    }

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
