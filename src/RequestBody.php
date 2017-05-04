<?php
namespace Serato\Slimulator;

use Slim\Http\Body;

/**
 * Provides a PSR-7 implementation of a reusable raw request body
 *
 * Changes from the standard `Slim\Http\RequestBody` include:
 *
 * - Does not copy the php://input stream to php://temp
 */
class RequestBody extends Body
{
    /**
     * Create a new RequestBody.
     */
    public function __construct()
    {
        $stream = fopen('php://temp', 'w+');
        parent::__construct($stream);
    }
}
