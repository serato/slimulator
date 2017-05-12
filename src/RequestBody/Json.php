<?php
namespace Serato\Slimulator\RequestBody;

use Serato\Slimulator\RequestBody\RequestBodyRawAbstract;

/**
 * Creates a request body consisting of a string of JSON-encoded data.
 */
class Json extends RequestBodyRawAbstract
{
    /**
     * Constructs the object
     *
     * @param string|array $json JSON request body content
     */
    public function __construct($json)
    {
        if (is_array($json)) {
            $json = json_encode($json);
        }
        parent::__construct($json);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return 'application/json';
    }
}
