<?php
declare(strict_types=1);

namespace Serato\Slimulator\RequestBody;

use Serato\Slimulator\RequestBody\RequestBodyWithParamsAbstract;

/**
 * Creates a request body of URL encoded name/value pairs.
 */
class UrlEncoded extends RequestBodyWithParamsAbstract
{
    /**
     * Create a new UrlEncoded
     *
     * @param array $params Request params
     *
     * @return static
     */
    public static function create(array $params = []): self
    {
        return new static($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }

    /**
     * {@inheritdoc}
     */
    public function getRawRequestBody(): string
    {
        return http_build_query($this->params);
    }
}
