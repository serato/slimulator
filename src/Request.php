<?php
namespace Serato\Slimulator;

use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\UploadedFile;
use Serato\Slimulator\RequestBody\RequestBodyStream;
use Serato\Slimulator\RequestBody\RequestBodyWithParamsAbstract;
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
 *
 * - The request body's stream interface
 * - A substitute for the `$_POST` PHP superglobal.
 * - A substitute for the `$_FILES` PHP superglobal.
 *
 * @link https://github.com/slimphp/Slim
 */
class Request extends SlimRequest
{
    /**
     * Create new HTTP request with data extracted from the EnvironmentBuilder object
     *
     * @param  EnvironmentBuilder   $environmentBuilder Slimulator EnvironmentBuilder object
     *
     * @return static
     */
    public static function createFromEnvironmentBuilder(
        EnvironmentBuilder $environmentBuilder
    ) {
        $environment    = $environmentBuilder->getSlimEnvironment();
        $method         = $environment['REQUEST_METHOD'];
        $uri            = Uri::createFromEnvironment($environment);
        $headers        = Headers::createFromEnvironment($environment);
        $cookieData     = $headers->get('Cookie', []);
        $cookies        = Cookies::parseHeader(isset($cookieData[0]) ? $cookieData[0] : '');
        $serverParams   = $environment->all();
        
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
            $requestBody = $environmentBuilder->getRequestBody();
            if ($requestBody instanceof RequestBodyWithParamsAbstract) {
                // FYI: Slim request class passes $_POST global
                // to $request->withParsedBody()
                $request = $request->withParsedBody(
                    $requestBody->getParams()
                );
            }
        }
        return $request;
    }
}
