<?php
namespace Serato\Slimulator\Test\Unit;

use PHPUnit\Framework\TestCase;
use Serato\Slimulator\UploadedFile;
use Serato\Slimulator\EnvironmentBuilder;
use Serato\Slimulator\RequestBody\Multipart;

/**
 * Unit tests for Serato\Slimulator\UploadedFile
 */
class UploadedFileTest extends TestCase
{
    public function testCreateFromEnvironmentBuilder()
    {
        $env = EnvironmentBuilder::create()->setRequestBody(
            Multipart::create()
                ->addFile('file1', $this->getTestFilePath(1))
                ->addFile('file2', $this->getTestFilePath(2))
        );

        $files = UploadedFile::createFromEnvironmentBuilder($env);

        $this->assertEquals(2, count(array_keys($files)));

        $this->assertEquals(
            (string)$files['file1']->getStream(),
            $this->getTestFileContents(1)
        );

        $this->assertEquals(
            (string)$files['file2']->getStream(),
            $this->getTestFileContents(2)
        );
    }

    private function getTestFilePath(int $num = 1)
    {
        return  __DIR__ . '/../resources/upload' . $num . '.txt';
    }

    private function getTestFileContents(int $num = 1)
    {
        return file_get_contents($this->getTestFilePath($num));
    }
}
