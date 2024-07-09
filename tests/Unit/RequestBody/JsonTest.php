<?php
declare(strict_types=1);

namespace Serato\Slimulator\Test\Unit\RequestBody;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestBody\Json;

/**
 * Unit tests for Serato\Slimulator\RequestBody\Json
 */
class JsonTest extends TestCase
{
    const DATA = ['var1' => 'val1', 'var2' => 'val2'];

    public function testConstructor(): void
    {
        $body1 = new Json(self::DATA);
        $body2 = Json::create(self::DATA);
        $jsonEncoded = json_encode(self::DATA);
        if ($jsonEncoded !== false) {
            $body3 = Json::create($jsonEncoded);
            $this->assertEquals($body2, $body3);
        }

        $this->assertEquals($body1, $body2);
    }

    public function testGetRawRequestBody(): void
    {
        $data = json_encode(self::DATA);
        if ($data !== false) {
            $body = Json::create($data);
            $this->assertEquals($data, $body->getRawRequestBody());
        }
    }

    public function testGetRequestBodyStream(): void
    {
        $data = json_encode(self::DATA);
        if ($data !== false) {
            $body = Json::create($data);
            $this->assertEquals($data, (string)$body->getRequestBodyStream());
        }
    }
}
