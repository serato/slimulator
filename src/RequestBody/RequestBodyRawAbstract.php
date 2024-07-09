<?php
declare(strict_types=1);

namespace Serato\Slimulator\RequestBody;

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
     * @param string|Array<string, mixed> $body Raw request body
     *
     * @return self
     */
    public static function create($body): self
    {
        // @phpstan-ignore-next-line (Because it is hard to fix this without rewriting bunch of stuff)
        return new static($body);
    }

    /**
     * Constructs the object
     *
     * @param string $body Raw request body
     */
    public function __construct(string $body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Returns the raw request body
     *
     * @return string Raw request body
     */
    public function getRawRequestBody(): string
    {
        return $this->body;
    }
}
