<?php
namespace Serato\Slimulator\Test\Unit\RequestBody;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestBody\RequestBodyStream;

/**
 * Unit tests for Serato\Slimulator\RequestBody\RequestBodyStream
 */
class RequestBodyStreamTest extends TestCase
{
    public function testConstruct()
    {
        $bodyStream = new RequestBodyStream;

        $str1 = 'string1';
        $this->assertEquals($bodyStream->write($str1), strlen($str1));
        $str2 = 'stringTwo';
        $this->assertEquals($bodyStream->write($str2), strlen($str2));
        $this->assertEquals((string)$bodyStream, $str1 . $str2);
    }
}
