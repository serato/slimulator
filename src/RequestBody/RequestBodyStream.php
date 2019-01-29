<?php
namespace Serato\Slimulator\RequestBody;

use Slim\Http\Body;
use Exception;

/**
 * Provides a PSR-7 implementation of a reusable raw request body
 *
 * Identical to `Slim\Http\RequestBody` except that it does not
 * copy the php://input stream to php://temp.
 */
class RequestBodyStream extends Body
{
    /**
     * Create a new RequestBodyStream.
     */
    public function __construct()
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new Exception('Unable to open temp file stream');
        }
        parent::__construct($stream);
    }
}
