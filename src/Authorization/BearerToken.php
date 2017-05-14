<?php
namespace Serato\Slimulator\Authorization;

use Serato\Slimulator\Authorization\HttpAuthorizationInterface;

/**
 * Creates PHP environment variables for a request using `Bearer` token authorization.
 */
class BearerToken implements HttpAuthorizationInterface
{
    /**
     * Token
     *
     * @var string
     */
    private $token;

    /**
     * Create a new BearerToken
     *
     * @param string    $token       Token
     *
     * @return static
     */
    public static function create(string $token): self
    {
        return new static($token);
    }

    /**
     * Constructs the object.
     *
     * @param string    $token       Token
     *
     * @return void
     */
    public function __construct(string $token)
    {
        $this->setToken($token);
    }

    /**
     * Sets the user bearer token value.
     *
     * @param string    $token       Token
     *
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Gets the bearer token value.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderValue(): string
    {
        return 'Bearer ' . $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpEnvVars(): array
    {
        return [];
    }
}
