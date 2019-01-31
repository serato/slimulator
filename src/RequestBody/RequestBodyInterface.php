<?php
declare(strict_types=1);

namespace Serato\Slimulator\RequestBody;

use Serato\Slimulator\RequestBody\RequestBodyStream;

/**
 * Interface that all RequestBody classes must implement.
 */
interface RequestBodyInterface
{
    /**
     * Returns the `Content-Type` header value for request body
     *
     * @returns string
     */
    public function getContentType(): string;

    /**
     * Returns request body as a RequestBodyStream
     *
     * @returns RequestBodyStream
     */
    public function getRequestBodyStream(): RequestBodyStream;

    /**
     * Returns the length in bytes of the request body
     *
     * @returns int
     */
    public function getContentLength(): int;

    /**
     * Returns the raw request body as a string
     *
     * @returns string
     */
    public function getRawRequestBody(): string;
}
