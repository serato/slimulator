<?php
declare(strict_types=1);

namespace Serato\Slimulator\Test\Unit\RequestBody;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestBody\UrlEncoded;

/**
 * Unit tests for Serato\Slimulator\RequestBody\UrlEncoded
 */
class UrlEncodedTest extends TestCase
{
    public function testConstructor()
    {
        $data = ['var1' => 'val1', 'var2' => 'val2'];

        $body1 = new UrlEncoded($data);
        $body2 = UrlEncoded::create($data);

        $this->assertEquals($body1, $body2);

        $body1 = new UrlEncoded();
        $body2 = UrlEncoded::create();

        $this->assertEquals($body1, $body2);
    }

    public function testAddRemoveParams()
    {
        $body = UrlEncoded::create();

        $data = ['var1' => 'val1', 'var2' => 'val2'];
        $body->addParams($data);
        $this->assertEquals($data, $body->getParams());

        $data2 = ['var3' => 'val3', 'var4' => 'val4'];
        $body->addParams($data2);
        $this->assertEquals(
            array_merge($data, $data2),
            $body->getParams()
        );

        $body = UrlEncoded::create();

        $body->addParam('var1', 'val1');
        $this->assertEquals(['var1' => 'val1'], $body->getParams());

        $body->addParam('var2', 'val2');
        $this->assertEquals(
            ['var1' => 'val1', 'var2' => 'val2'],
            $body->getParams()
        );

        $body->removeParam('var1');
        $this->assertEquals(['var2' => 'val2'], $body->getParams());

        $body->removeParam('doesnt_exist');
        $this->assertEquals(['var2' => 'val2'], $body->getParams());
    }

    public function testGetRequestBodyStream()
    {
        $body = new UrlEncoded(['var1' => 'val1', 'var2' => 'val2']);
        $this->assertEquals(
            (string)$body->getRequestBodyStream(),
            $body->getRawRequestBody()
        );
    }
}
