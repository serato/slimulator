<?php
namespace Serato\Slimulator\RequestBody;

use Serato\Slimulator\RequestBody\RequestBodyInterface;
use Serato\Slimulator\RequestBody\RequestBodyStream;

abstract class RequestBodyAbstract implements RequestBodyInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function getContentType(): string;

    /**
     * {@inheritdoc}
     */
    public function getRequestBodyStream(): RequestBodyStream
    {
        $stream = new RequestBodyStream;
        $stream->write($this->getRawRequestBody());
        $stream->rewind();
        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentLength(): int
    {
        return $this->getRawRequestBody() * 8;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getRawRequestBody(): string;
}
