<?php
namespace Serato\Slimulator;

use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\UploadedFile;
use Serato\Slimulator\RequestBody\RequestBodyStream;
use Slim\Http\Request as SlimRequest;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Cookies;

/**
 * Extends `Slim\Http\Request` and adds a `self::createFromEnvironmentBuilder` method.
 *
 * The `self::createFromEnvironmentBuilder` method creates the Request using a
 * `Serato\Slimulator\EnvironmentBuilder` instance to provide:
 * - the request body's stream interface
 * - a substitute for the `$_POST` PHP superglobal.
 * - a substitute for the `$_FILES` PHP superglobal.
 *
 * @link https://github.com/slimphp/Slim
 */
class Request extends SlimRequest
{
    /**
     * Create new HTTP request with data extracted from the EnvironmentBuilder object
     *
     * @param  Environment          $environment        Slim Environment
     * @param  EnvironmentBuilder   $environmentBuilder Slimulator EnvironmentBuilder object
     *
     * @return static
     */
    public static function createFromEnvironmentBuilder(
        Environment $environment,
        EnvironmentBuilder $environmentBuilder
    ) {
        $method = $environment['REQUEST_METHOD'];
        $uri = Uri::createFromEnvironment($environment);
        $headers = Headers::createFromEnvironment($environment);
        $cookies = Cookies::parseHeader($headers->get('Cookie', []));
        $serverParams = $environment->all();
        
        // Use the stream from the $environmentBuilder
        // FYI: Slim Request class uses `Slim\Http\RequestBody` class
        // which reads php://input stream
        $body = null;
        if ($environmentBuilder->getRequestBody() === null) {
            $body = new RequestBodyStream;
        } else {
            $body = $environmentBuilder->getRequestBody()->getRequestBodyStream();
        }
        
        // FYI: Slim Request class creates $uploaded files from
        // Slim\Http\UploadedFile::createFromEnvironment($environment)
        $uploadedFiles = UploadedFile::createFromEnvironmentBuilder($environmentBuilder);

        $request = new static($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        if ($method === 'POST' &&
            in_array($request->getMediaType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])
        ) {
            if (is_a(
                $environmentBuilder->getRequestBody(),
                'Serato\Slimulator\RequestBody\RequestBodyWithParamsAbstract'
            )) {
                // FYI: Slim request class passes $_POST global
                // to $request->withParsedBody()
                $request = $request->withParsedBody(
                    $environmentBuilder->getRequestBody()->getParams()
                );
            }
        }
        return $request;
    }
}
