<?php
namespace Serato\Slimulator\Authorization;

use Serato\Slimulator\Authorization\HttpAuthorizationInterface;

/**
 * Creates PHP environment variables for a request using HTTP `Basic` authorization.
 */
class BasicAuthorization implements HttpAuthorizationInterface
{

    /**
     * User name
     *
     * @var string
     */
    private $name;

    /**
     * Password
     *
     * @var string
     */
    private $password;

    /**
     * Constructs the object.
     *
     * @param string    $name       User name
     * @param string    $Password   Password
     *
     * @return void
     */
    public function __construct(string $name, string $password)
    {
        $this->setName($name);
        $this->setPassword($password);
    }

    /**
     * Sets the user name.
     *
     * @param string    $name       User name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the password.
     *
     * @param string    $Password  Password
     *
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Gets the user name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the password.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderValue(): string
    {
        return 'Basic ' . base64_encode($this->getName() . ':' . $this->getPassword());
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpEnvVars(): array
    {
        return [
            'PHP_AUTH_USER' => $this->getName(),
            'PHP_AUTH_PW'   => $this->getPassword()
        ];
    }
}
