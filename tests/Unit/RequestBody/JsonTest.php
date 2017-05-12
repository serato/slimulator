<?php
namespace Serato\Slimulator\Test\Unit\RequestBody;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestBody\Json;

/**
 * Unit tests for Serato\Slimulator\RequestBody\Json
 */
class JsonTest extends TestCase
{
    const DATA = ['var1' => 'val1', 'var2' => 'val2'];

    public function testConstructor()
    {
        $body1 = new Json(self::DATA);
        $body2 = Json::create(self::DATA);
        $body3 = Json::create(json_encode(self::DATA));

        $this->assertEquals($body1, $body2);
        $this->assertEquals($body2, $body3);
    }

    public function testGetRawRequestBody()
    {
        $data = json_encode(self::DATA);
        $body = Json::create($data);
        $this->assertEquals($data, $body->getRawRequestBody());
    }

    public function testGetRequestBodyStream()
    {
        $data = json_encode(self::DATA);
        $body = Json::create($data);
        $this->assertEquals($data, (string)$body->getRequestBodyStream());
    }
}
