<?php
namespace Serato\Slimulator\Test\Authorization;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\Authorization\BearerToken;

/**
 * Unit tests for Serato\Slimulator\Authorization\BearerToken
 */
class BearerTokenTest extends TestCase
{
    public function testGetSet()
    {
        $token = 'my_big_log_token';

        $auth = new BearerToken($token);

        $this->assertEquals($auth->getToken(), $token);

        $token = 'my_big_log_token_number_2';

        $auth->setToken($token);

        $this->assertEquals($auth->getToken(), $token);
    }

    public function testHeaderValue()
    {
        $token = 'my_big_log_token';

        $auth = new BearerToken($token);

        $headerVal = $auth->getHeaderValue();

        $this->assertEquals(strpos($headerVal, 'Bearer '), 0);
        $this->assertTrue(strpos($headerVal, $token) > 0);
    }
}
