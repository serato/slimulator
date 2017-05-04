<?php
namespace Serato\Slimulator\Test;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\HttpBasicAuthorization;

/**
 * Unit tests for Serato\Slimulator\HttpBasicAuthorization
 */
class HttpBasicAuthorizationTest extends TestCase
{
    public function testGetSet()
    {
        $user_name = 'myuser';
        $user_pass = 'mypass';

        $auth = new HttpBasicAuthorization($user_name, $user_pass);

        $this->assertEquals($auth->getName(), $user_name);
        $this->assertEquals($auth->getPassword(), $user_pass);

        $user_name = 'myuser2';
        $user_pass = 'mypass2';

        $auth->setName($user_name);
        $auth->setPassword($user_pass);

        $this->assertEquals($auth->getName(), $user_name);
        $this->assertEquals($auth->getPassword(), $user_pass);
    }

    public function testHeaderValue()
    {
        $user_name = 'myuser';
        $user_pass = 'mypass';

        $auth = new HttpBasicAuthorization($user_name, $user_pass);

        $headerVal = $auth->getHeaderValue();

        $this->assertTrue(base64_decode($headerVal) !== false);

        $data = base64_decode($headerVal);
        $this->assertEquals(strpos($data, $user_name), 0);
        $this->assertTrue(strpos($data, $user_pass) > 0);
    }

    public function testGetPhpEnvVars()
    {
        $user_name = 'myuser';
        $user_pass = 'mypass';

        $auth = new HttpBasicAuthorization($user_name, $user_pass);

        $vars = $auth->getPhpEnvVars();

        $this->assertEquals($vars['PHP_AUTH_USER'], $user_name);
        $this->assertEquals($vars['PHP_AUTH_PW'], $user_pass);
    }
}
