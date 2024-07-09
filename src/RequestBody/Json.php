<?php
declare(strict_types=1);

namespace Serato\Slimulator\RequestBody;

/**
 * Creates a request body consisting of a string of JSON-encoded data.
 */
class Json extends RequestBodyRawAbstract
{
    /**
     * Constructs the object
     *
     * @param string|Array<string,mixed> $json JSON request body content
     */
    public function __construct($json)
    {
        if (is_array($json)) {
            $jsonEncodedString = json_encode($json);
            if ($jsonEncodedString !== false) {
                $json = $jsonEncodedString;
            } else {
                $json = '';
            }
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
