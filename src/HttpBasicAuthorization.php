<?php
namespace Serato\Slimulator;

use Serato\Slimulator\HttpAuthorizationInterface;

class HttpBasicAuthorization implements HttpAuthorizationInterface {

    private $name;
    private $password;

    public function __construct(string $name, string $password){
        $this->setName($name);
        $this->setPassword($password);
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    // Interface implementation

    public function getHeaderValue(): string
    {
        return base64_encode($this->getName() . ':' . $this->getPassword());
    }

    public function getPhpEnvVars(): array
    {
        return [
            'PHP_AUTH_USER' => $this->getName(),
            'PHP_AUTH_PW'   => $this->getPassword()
        ];
    }
}