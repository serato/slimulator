<?php
declare(strict_types=1);

namespace Serato\Slimulator\RequestBody;

use Serato\Slimulator\RequestBody\RequestBodyAbstract;

/**
 * A request body consisting of raw unparsed text data of the type
 * specified by the `self::getContentType` method.
 */
abstract class RequestBodyRawAbstract extends RequestBodyAbstract
{
    /**
     * Raw request body
     *
     * @var string
     */
    protected $body;

    /**
     * Create a new instance
     *
     * @param mixed $body Raw request body
     *
     * @return static
     */
    public static function create($body): self
    {
        return new static($body);
    }

    /**
     * Constructs the object
     *
     * @param mixed $body Raw request body
     */
    public function __construct($body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawRequestBody(): string
    {
        return (string)$this->body;
    }
}
