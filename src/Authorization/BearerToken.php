<?php
namespace Serato\Slimulator\Authorization;

use Serato\Slimulator\Authorization\HttpAuthorizationInterface;

/**
 * Creates environment variables for a request using Bearer token authorization.
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
     * Constructs the object
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
     * Sets the user name
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
     * Gets the token
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
