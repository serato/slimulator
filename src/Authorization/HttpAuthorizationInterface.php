<?php
namespace Serato\Slimulator\Authorization;

/**
 * Interface that all HTTP Authorization classes must implement
 */
interface HttpAuthorizationInterface
{

    /**
     * Returns a value for use within an `Authorization` HTTP header
     */
    public function getHeaderValue(): string;

    /**
     * Returns an array of all PHP environment variables created by the
     * authorization scheme.
     */
    public function getPhpEnvVars(): array;
}
