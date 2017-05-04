<?php
namespace Serato\Slimulator;

interface HttpAuthorizationInterface {

    public function getHeaderValue(): string;

    public function getPhpEnvVars(): array;
}