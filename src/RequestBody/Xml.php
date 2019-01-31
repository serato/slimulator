<?php
declare(strict_types=1);

namespace Serato\Slimulator\RequestBody;

use Serato\Slimulator\RequestBody\RequestBodyRawAbstract;

/**
 * Creates a request body consisting of a string of XML.
 */
class Xml extends RequestBodyRawAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return 'application/xml';
    }
}
