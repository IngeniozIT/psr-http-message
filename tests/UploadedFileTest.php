<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;
use IngeniozIT\Http\Message\Exceptions\InvalidArgumentException;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\UploadedFile
 */
class UploadedFileTest extends TestCase
{
    /** @var bool True to make fopen fail. */
    public static $fopen = false;

    /**
     * Before each test, reset functions overrides.
     */
    protected function setUp(): void
    {
        self::$fopen = false;
    }

    /**
     * Open a temporary file and return its path.
     *
     * @return string
     */
    protected function getFilePath($withContent = false)
    {
        $tmpFd = tmpfile();
        $path = stream_get_meta_data($tmpFd)['uri'];
        fclose($tmpFd);

        if ($withContent) {
            file_put_contents($path, '');
        } elseif (file_exists($path)) {
            unlink($path);
        }

        return $path;
    }

    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get an UploadedFile instance.
     * @param  ?int $size [description]
     * @param  ?int $error [description]
     * @param  ?string $clientFilename [description]
     * @param  ?string $clientMediaType [description]
     * @return UploadedFileInterface
     */
    protected function getUploadedFile(?string $path = null, ?int $size = null, ?int $error = null, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        if ($path === null) {
            $path = $this->getFilePath(true);
        }

        if ($clientMediaType !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($path, $size, $error, $clientFilename, $clientMediaType);
        }

        if ($clientFilename !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($path, $size, $error, $clientFilename);
        }

        if ($error !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($path, $size, $error);
        }

        if ($size !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($path, $size);
        }

        return new \IngeniozIT\Http\Message\UploadedFile($path);
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getUploadedFile() return a UploadedFileInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(UploadedFileInterface::class, $this->getUploadedFile(), 'getUploadedFile does not give an UploadedFileInterface object.');
    }

    /**
     * Passing an invalid file path should throw an exception.
     */
    public function testConstructWithInvalidPath()
    {
        // Contains a path to a file that does not exist.
        $nonExistantPath = $this->getFilePath(false);

        $this->expectException(\RuntimeException::class);
        $this->getUploadedFile($nonExistantPath);
    }

    /**
     * A filesystem error should trigger an exception.
     */
    public function testConstructWithFilesystemError()
    {
        // Trigger a fopen failure
        self::$fopen = true;

        $this->expectException(\RuntimeException::class);
        $this->getUploadedFile();
    }

    // ========================================== //
    // Stream                                     //
    // ========================================== //

    /**
     * Retrieve a stream representing the uploaded file.
     */
    public function testGetStream()
    {
        $uploadedFile = $this->getUploadedFile();

        $this->assertInstanceOf(\Psr\Http\Message\StreamInterface::class, $uploadedFile->getStream());
    }

    /**
     * Retrieve a stream representing the uploaded file.
     * throws \RuntimeException in cases when no stream can be created.
     */
    public function testGetStreamMoved()
    {
        $uploadedFile = $this->getUploadedFile();

        $uploadedFile->moveTo($this->getFilePath());

        $this->expectException(\RunTimeException::class);
        $uploadedFile->getStream();
    }

    // ========================================== //
    // Move To                                    //
    // ========================================== //

    /**
     * Move the uploaded file to a new location.
     */
    public function testMoveTo()
    {
        $path = $this->getFilePath(true);
        file_put_contents($path, 'foo bar baz !');

        $uploadedFile = $this->getUploadedFile($path);

        $targetPath = $this->getFilePath();
        $uploadedFile->moveTo($targetPath);

        $this->assertFileExists($targetPath);
        $this->assertSame(file_get_contents($targetPath), 'foo bar baz !');
    }

    /**
     * Move the uploaded file to a new location.
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     */
    public function testMoveToMoved()
    {
        $path = $this->getFilePath(true);

        $uploadedFile = $this->getUploadedFile($path);

        $targetPath = $this->getFilePath();
        // Call moveTo a first time
        $uploadedFile->moveTo($targetPath);

        $this->expectException(\RunTimeException::class);
        // Call moveTo a second time
        $uploadedFile->moveTo($targetPath);
    }

    /**
     * Move the uploaded file to a new location.
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     */
    public function testMoveToInvalidTargetPath()
    {
        $uploadedFile = $this->getUploadedFile();
        $path = $this->getFilePath(true);

        $this->expectException(\InvalidArgumentException::class);
        $uploadedFile->moveTo($path);
    }

    // ========================================== //
    // Get Size                                   //
    // ========================================== //

    /**
     * Retrieve the file size.
     */
    public function testGetSize()
    {
        $uploadedFile = $this->getUploadedFile(null, 42);

        $this->assertSame(42, $uploadedFile->getSize());
    }

    /**
     * Retrieve the file size.
     * return int|null The file size in bytes or null if unknown.
     */
    public function testGetSizeWithUnknownSize()
    {
        $uploadedFile = $this->getUploadedFile(null);

        $this->assertNull($uploadedFile->getSize());
    }

    // ========================================== //
    // Get Error                                  //
    // ========================================== //

    /**
     * Retrieve the error associated with the uploaded file.
     */
    public function testGetError()
    {
        $uploadedFile = $this->getUploadedFile(null, null, \UPLOAD_ERR_CANT_WRITE);

        $this->assertSame(\UPLOAD_ERR_CANT_WRITE, $uploadedFile->getError());
    }

    /**
     * Retrieve the error associated with the uploaded file.
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * @dataProvider getGetErrorWithAllValidErrorsProvider
     */
    public function testGetErrorWithAllValidErrors(int $error)
    {
        $uploadedFile = $this->getUploadedFile(null, null, $error);

        $this->assertSame($error, $uploadedFile->getError());
    }

    /**
     * Provider. Gives all possible valid uploaded file errors.
     */
    public function getGetErrorWithAllValidErrorsProvider(): array
    {
        return [
            '\UPLOAD_ERR_OK' => [\UPLOAD_ERR_OK], // 0
            '\UPLOAD_ERR_INI_SIZE' => [\UPLOAD_ERR_INI_SIZE], // 1
            '\UPLOAD_ERR_FORM_SIZE' => [\UPLOAD_ERR_FORM_SIZE], // 2
            '\UPLOAD_ERR_PARTIAL' => [\UPLOAD_ERR_PARTIAL], // 3
            '\UPLOAD_ERR_NO_FILE' => [\UPLOAD_ERR_NO_FILE], // 4
            '\UPLOAD_ERR_NO_TMP_DIR' => [\UPLOAD_ERR_NO_TMP_DIR], // 6
            '\UPLOAD_ERR_CANT_WRITE' => [\UPLOAD_ERR_CANT_WRITE], // 7
            '\UPLOAD_ERR_EXTENSION' => [\UPLOAD_ERR_EXTENSION], // 8
        ];
    }

    /**
     * Retrieve the error associated with the uploaded file.
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * @dataProvider getGetErrorWithInvalidErrorsProvider
     */
    public function testGetErrorWithInvalidErrors(int $error)
    {
        $this->expectException(\InvalidArgumentException::class);
        $uploadedFile = $this->getUploadedFile(null, null, $error);
    }

    /**
     * Provider. Gives invalid uploaded file errors.
     */
    public function getGetErrorWithInvalidErrorsProvider(): array
    {
        return [
            '-1' => [-1],
            '9' => [9],
            '42' => [42],
            '128' => [128],
            '-42' => [-42],
        ];
    }

    // ========================================== //
    // Client File Name                           //
    // ========================================== //

    /**
     * Retrieve the filename sent by the client.
     */
    public function testGetClientFileName()
    {
        /** @var StreamInterface $mockStreamInterface */
        $stream = $this->createMock(StreamInterface::class);

        $uploadedFile = $this->getUploadedFile(null, null, 0, 'fileName.test');

        $this->assertSame('fileName.test', $uploadedFile->getClientFilename());
    }

    /**
     * Retrieve the filename sent by the client.
     * return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function testGetClientFileNameWithUnknownFileName()
    {
        $uploadedFile = $this->getUploadedFile();

        $this->assertNull($uploadedFile->getClientFilename());
    }

    // ========================================== //
    // Client Media Type                          //
    // ========================================== //

    /**
     * Retrieve the media type sent by the client.
     */
    public function testGetClientMediaType()
    {
        /** @var StreamInterface $mockStreamInterface */
        $stream = $this->createMock(StreamInterface::class);

        $uploadedFile = $this->getUploadedFile(null, null, 0, null, 'MIME/TYPE');

        $this->assertSame('MIME/TYPE', $uploadedFile->getClientMediaType());
    }

    /**
     * Retrieve the media type sent by the client.
     * return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function testGetClientMediaTypeWithUnknownMediaType()
    {
        $uploadedFile = $this->getUploadedFile();

        $this->assertNull($uploadedFile->getClientMediaType());
    }
}

// ========================================== //
// Filesystem overrides                       //
// ========================================== //

namespace IngeniozIT\Http\Message;

function fopen(string $filename, string $mode)
{
    return \IngeniozIT\Http\Message\Tests\UploadedFileTest::$fopen ? false : \fopen($filename, $mode);
}
