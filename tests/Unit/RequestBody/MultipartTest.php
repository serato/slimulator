<?php
namespace Serato\Slimulator\Test\Unit\RequestBody;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\RequestBody\Multipart;

/**
 * Unit tests for Serato\Slimulator\RequestBody\Multipart
 */
class MultipartTest extends TestCase
{
    public function testConstruct()
    {
        $params = ['var1' => 'val1'];
        $body = Multipart::create(
            $params,
            ['file1' => $this->getTestFile()]
        );
        $this->assertEquals($params, $body->getParams());
        $this->assertTrue(is_array($body->getFiles()));
    }

    public function testAddGetFiles()
    {
        $body = Multipart::create();
        
        $body->addFile('file1', $this->getTestFile(1));
        $body->addFile('file2', $this->getTestFile(2));
        
        $fileInfo = $body->getFiles();

        $this->assertTrue(is_array($body->getFiles()));

        if (is_array($fileInfo)) {
            $this->assertEquals(2, count(array_keys($fileInfo)));
            
            $this->assertTrue(is_array($fileInfo['file1']));
            $this->assertEquals($fileInfo['file1']['tmp_name'], $this->getTestFile(1));

            $this->assertTrue(is_array($fileInfo['file2']));
            $this->assertEquals($fileInfo['file2']['tmp_name'], $this->getTestFile(2));
        }
    }

    private function getTestFile(int $num = 1)
    {
        return __DIR__ . '/../../resources/upload' . $num . '.txt';
    }
}
