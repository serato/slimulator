<?php
namespace Serato\Slimulator\RequestBody;

use Exception;
use Serato\Slimulator\RequestBody\RequestBodyWithParamsAbstract;

/**
 * Creates a request body consisting of a `mulipart/form-data` content type
 * and mutiple body parts.
 *
 * The `mulipart/form-data` content type allows for both form data and file uploads
 * to be encoded into the request body.
 *
 * PHP exposes form data via the `$_POST` superglobal and file uploads via the
 * `$_FILES` superglobal.
 *
 * For HTTP POST requests whose content type is `mulipart/form-data`, the Slim
 * framework references the `$_POST` and `$_FILES` superglobals rather than the
 * raw request body.
 *
 * Therefore, this class does not create a raw request body in the
 * `self::getRawRequestBody` method.
 *
 * Instead, the value returned by the `self::getParams` method can be used in place
 * of the `$_POST` superglobal and the value returned by `self::getFiles` can be
 * used in place of the `$_FILES` superglobal.
 *
 * Note: See the PHP documentation on the `$_FILES` superglobal for more information
 * about what properties are available from this superglobal.
 *
 * @link http://php.net/manual/en/reserved.variables.files.php
 *
 * Note: the HTTP specification states that only POST requests can identity request
 * bodies whose content type is `mulipart/form-data`.
 *
 * Accordingly, the Slim framework only exposes data contained within a
 * `mulipart/form-data` request body when the HTTP method is POST.
 *
 * But, by design, no attempt is made to enforce the correct use of HTTP method
 * when using this class to created request bodies.
 *
 * ie. Testing scenarios may require the construction of invalid request bodies for
 * a given HTTP method.
 */
class Multipart extends RequestBodyWithParamsAbstract
{
    const BOUNDARY = '--------------------------477985590996817534165738';

    private $files = [];

    /**
     * Create a new Multipart
     *
     * @param array $params Name/value array of request parameters
     * @param array $files  Name/file path array of files
     *
     * @return static
     */
    public static function create(array $params = [], array $files = []): self
    {
        $requestBody = new static($params);
        return $requestBody->addFiles($files);
    }

    /**
     * Adds a single file to the request body.
     *
     * The upload status of each file can be specified with the `$uploadStatus`
     * parameter. See the PHP documentation for a list of all values upload status'.
     *
     * @link http://php.net/manual/en/reserved.variables.files.php
     *
     * @param string    $name           Request parameter name
     * @param mixed     $filePath       Path to file
     * @param int       uploadStatus    Status of the file upload process
     *
     * @return self
     */
    public function addFile(string $name, string $filePath, int $uploadStatus = UPLOAD_ERR_OK): self
    {
        if (!file_exists($filePath)) {
            throw new Exception("File '$filePath' not found.");
        }
        $this->files[$name] = [
            'name'      => basename($filePath),
            'type'      => mime_content_type($filePath),
            'tmp_name'  => $filePath,
            'error'     => $uploadStatus,
            'size'      => filesize($filePath)
        ];
        return $this;
    }

    /**
     * Adds multiple files to the request body.
     *
     * @param array $files     Name/file path array of files
     *
     * @return self
     */
    public function addFiles(array $files): self
    {
        foreach ($files as $name => $filePath) {
            $this->addFile($name, $filePath);
        }
        return $this;
    }

    /**
     * Removes a file from the request body.
     *
     * @return self
     */
    public function removeFile(string $name): self
    {
        unset($this->files[$name]);
        return $this;
    }

    /**
     * Returns all files in the request body.
     *
     * Analogous to the `$_FILES` PHP superglobal.
     *
     * @return array|null
     */
    public function getFiles()
    {
        return count($this->files) === 0 ? null : $this->files;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return 'multipart/form-data; boundary=' . self::BOUNDARY;
    }

    /**
     * Returns the length (in bytes) of the request body
     *
     * For `mulipart/form-data` request bodies this value is only approximate
     * and should not be considered 100% accurate.
     *
     * @returns int
     */
    public function getContentLength(): int
    {
        $length = 0;
        if ($this->getFiles() !== null) {
            foreach ($this->getFiles() as $name => $file) {
                $length += ((strlen(self::BOUNDARY) + 40 + strlen($name)) * 8) +
                            $file['size'];
            }
        }
        foreach ($this->getParams() as $name => $value) {
            $length += (
                strlen(self::BOUNDARY) + 40 + strlen($name)+ strlen($value)
            ) * 8;
        }
        return $length;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawRequestBody(): string
    {
        // Not required for multipart request bodies. See comments in class
        // description.
        return '';
    }
}
