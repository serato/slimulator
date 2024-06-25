<?php
declare(strict_types=1);

namespace Serato\Slimulator\Test\Unit\RequestBody;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestBody\Xml;

/**
 * Unit tests for Serato\Slimulator\RequestBody\Xml
 */
class XmlTest extends TestCase
{
    const DATA = '<xml><node>value</node></xml>';

    public function testConstructor(): void
    {
        $body1 = new Xml(self::DATA);
        $body2 = Xml::create(self::DATA);

        $this->assertEquals($body1, $body2);
    }

    public function testGetRawRequestBody(): void
    {
        $body = Xml::create(self::DATA);
        $this->assertEquals(self::DATA, $body->getRawRequestBody());
    }

    public function testGetRequestBodyStream(): void
    {
        $body = Xml::create(self::DATA);
        $this->assertEquals(self::DATA, (string)$body->getRequestBodyStream());
    }
}
