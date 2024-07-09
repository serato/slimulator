<?php
declare(strict_types=1);

namespace Serato\Slimulator;

use Slim\Http\UploadedFile as SlimUploadedFile;

/**
 * Extends `Slim\Http\UploadedFile` and adds a `self::createFromEnvironmentBuilder`
 * method.
 *
 * The `self::createFromEnvironmentBuilder` method uses the request body from a
 * `Serato\Slimulator\EnvironmentBuilder` instance to provide a substitute for the
 * `$_FILES` PHP superglobal.
 *
 * @link https://github.com/slimphp/Slim
 */
final class UploadedFile extends SlimUploadedFile
{
    /**
     * Create a normalized tree of UploadedFile instances from the EnvironmentBuilder.
     *
     * @param EnvironmentBuilder $environmentBuilder The environment builder
     *
     * @return Array<string, mixed>|null A normalized tree of UploadedFile instances or null if none are provided.
     */
    public static function createFromEnvironmentBuilder(EnvironmentBuilder $environmentBuilder): ?array
    {
        $files = [];

        if ($environmentBuilder->getRequestBody() !== null &&
            is_a($environmentBuilder->getRequestBody(), 'Serato\Slimulator\RequestBody\Multipart') &&
            $environmentBuilder->getRequestBody()->getFiles() !== null
        ) {
            foreach ($environmentBuilder->getRequestBody()->getFiles() as $name => $file) {
                $files[$name] = new static(
                    $file['tmp_name'],
                    $file['name'],
                    $file['type'],
                    $file['size'],
                    $file['error'],
                    true
                );
            }
        }

        return $files;
    }
}
