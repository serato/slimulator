<?php
declare(strict_types=1);
namespace Serato\Slimulator\Authorization;

use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\Types\Array_;

/**
 * Creates PHP environment variables for a request using HTTP `Basic` authorization.
 */
final class BasicAuthorization implements HttpAuthorizationInterface
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
     * Create a new BasicAuthorization
     *
     * @param string    $name       User name
     * @param string    $password   Password
     *
     * @return static
     */
    public static function create(string $name, string $password): self
    {
        return new static($name, $password);
    }

    /**
     * Constructs the object.
     *
     * @param string    $name       User name
     * @param string    $password   Password
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
     * @param string  $password  Password
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
     * @return Array<string, string>
     */
    public function getHeaders(): array
    {
        return [
            'Authorization' => $this->getHeaderValue()
        ];
    }

    /**
     * @return  Array<string, string>
     */
    public function getQueryParams(): array
    {
        return [];
    }

    /**
     * @return  Array<string, string>
     */
    public function getPostData(): array
    {
        return [];
    }

    /**
     * @return  Array<string, string>
     */
    public function getFiles(): array
    {
        return [];
    }

    /**
     * @return  Array<string, string>
     */
    public function getCookies(): array
    {
        return [];
    }

    /**
     * @return  Array<string, string>
     */
    public function getServer(): array
    {
        return [];
    }

    /**
     * @return  Array<string, string>
     */
    public function getPhpEnvVars(): array
    {
        return [
            'PHP_AUTH_USER' => $this->getName(),
            'PHP_AUTH_PW'   => $this->getPassword()
        ];
    }
}
