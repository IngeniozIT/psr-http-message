<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{UploadedFileInterface, StreamInterface};

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\UploadedFile
 */
class UploadedFileTest extends TestCase
{
    public static $rename = null;
    public static $is_uploaded_file = null;
    public static $move_uploaded_file = null;
    public static $file_put_contents = null;
    public static $fopen = null;
    public static $realpath = null;
    public static $php_sapi_name = null;
    public static $is_resource = null;

    /**
     * After each test, reset functions overrides.
     */
    protected function tearDown(): void
    {
        self::$rename = null;
        self::$is_uploaded_file = null;
        self::$move_uploaded_file = null;
        self::$file_put_contents = null;
        self::$fopen = null;
        self::$realpath = null;
        self::$php_sapi_name = null;
        self::$is_resource = null;
    }

    /**
     * Open a temporary file and return its path.
     *
     * @return string
     */
    protected function getFilePath(): string
    {
        $path = $this->getEmptyPath();
        touch($path);
        return $path;
    }

    protected function getEmptyPath(): string
    {
        return sys_get_temp_dir() . '/' . uniqid('UploadedFileTest', true);
    }

    protected function getTempFilePath(): string
    {
        return tempnam(sys_get_temp_dir(), 'UploadedFileTest');
    }

    /**
     * @suppress PhanAccessMethodInternal
     * @suppress PhanTypeMismatchReturn
     */
    protected function getStreamMock(?string $path = null, $methods = []): StreamInterface
    {
        if ($path === null) {
            $path = $this->getTempFilePath();
        }

        $streamMock = $this->createMock(StreamInterface::class);

        foreach ($methods as $name => $returnValue) {
            $streamMock->method($name)->willReturn($returnValue);
        }

        return $streamMock;
    }

    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get an UploadedFile instance.
     *
     * @param  ?int    $size            [description]
     * @param  ?int    $error           [description]
     * @param  ?string $clientFilename  [description]
     * @param  ?string $clientMediaType [description]
     * @return UploadedFileInterface
     */
    protected function getUploadedFile(?string $path = null, ?int $size = null, ?int $error = null, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        $mockStream = $this->getStreamMock($path, [
            'getContents' => 'foo bar baz !',
        ]);

        $errorToUse = $error ?? \UPLOAD_ERR_OK;

        if ($clientMediaType !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($mockStream, $size, $errorToUse, $clientFilename, $clientMediaType);
        }

        if ($clientFilename !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($mockStream, $size, $errorToUse, $clientFilename);
        }

        if ($error !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($mockStream, $size, $errorToUse);
        }

        if ($size !== null) {
            return new \IngeniozIT\Http\Message\UploadedFile($mockStream, $size);
        }

        return new \IngeniozIT\Http\Message\UploadedFile($mockStream);
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

        $uploadedFile->moveTo($this->getEmptyPath());

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
        $path = $this->getFilePath();
        file_put_contents($path, 'foo bar baz !');

        $uploadedFile = $this->getUploadedFile($path);

        $targetPath = $this->getEmptyPath();
        $uploadedFile->moveTo($targetPath);

        $this->assertFileExists($targetPath);
        $this->assertSame(file_get_contents($targetPath), 'foo bar baz !');
    }

    /**
     * Move the uploaded file to a new location.
     * throws \RuntimeException on the second or subsequent call to the method.
     */
    public function testMoveToMoved()
    {
        $path = $this->getFilePath();

        $uploadedFile = $this->getUploadedFile($path);

        $targetPath = $this->getEmptyPath();
        // Call moveTo a first time
        $uploadedFile->moveTo($targetPath);

        $this->expectException(\RunTimeException::class);
        // Call moveTo a second time
        $uploadedFile->moveTo($targetPath);
    }

    /**
     * Move the uploaded file to a new location.
     * throws \InvalidArgumentException if the $targetPath specified is invalid.
     */
    public function testMoveToInvalidTargetPath()
    {
        $uploadedFile = $this->getUploadedFile();
        $path = 'this will fail';
        self::$realpath = false;

        $this->expectException(\InvalidArgumentException::class);
        $uploadedFile->moveTo($path);
    }

    /**
     * Move the uploaded file to a new location.
     * throws \InvalidArgumentException if the $targetPath specified already exists.
     */
    public function testMoveToExistingTargetPath()
    {
        $uploadedFile = $this->getUploadedFile();
        $path = $this->getFilePath();

        $this->expectException(\InvalidArgumentException::class);
        $uploadedFile->moveTo($path);
    }

    /**
     * Move the uploaded file to a new location.
     * throws \RuntimeException on any error during the move operation.
     * @dataProvider providerFsErrorCases
     */
    public function testMoveToThrowsExceptionOnFilesystemError(bool $isCli, bool $streamWithUri, bool $overrideFopen)
    {
        $uploadedFile = $this->getUploadedFile();
        $path = $this->getEmptyPath();
        $mockStream = $this->getStreamMock($path, [
            'getContents' => 'foo bar baz !',
            'getMetadata' => $streamWithUri ? 'test_uri' : null,
        ]);
        $uploadedFile = new \IngeniozIT\Http\Message\UploadedFile($mockStream);
        $path = $this->getEmptyPath();

        self::$rename = false;
        self::$move_uploaded_file = false;
        self::$file_put_contents = false;
        self::$fopen = false;
        self::$php_sapi_name = $isCli ? 'cli' : 'not_cli';
        if ($overrideFopen) {
            self::$fopen = false;
        }

        $this->expectException(\RuntimeException::class);
        $uploadedFile->moveTo($path);
    }

    /**
     * Provider. Gives filesystem error cases based on
     * - cli / not cli environment
     * - StreamInterface object with / without uri, metadata, fopen fail
     * @return array
     */
    public function providerFsErrorCases(): array
    {
        return [
            'cli + uri' => [true, true, false],
            'cli + no uri' => [true, false, false],
            'not cli + uri' => [false, true, false],
            'not cli + no uri' => [false, false, false],
            'cli + no uri + fopen fail' => [false, false, true],
        ];
    }

    /**
     * Move the uploaded file to a new location.
     * @dataProvider providerFsWorkingCases
     */
    public function testMoveToWorksWithAllEnvs(bool $isCli, bool $streamWithUri)
    {
        $uploadedFile = $this->getUploadedFile();
        $path = $this->getEmptyPath();
        $mockStream = $this->getStreamMock($path, [
            'getContents' => 'foo bar baz !',
            'getMetadata' => $streamWithUri ? 'test_uri' : null,
        ]);
        $uploadedFile = new \IngeniozIT\Http\Message\UploadedFile($mockStream);
        $path = $this->getEmptyPath();
        self::$rename = true;
        self::$is_uploaded_file = true;
        self::$move_uploaded_file = true;
        self::$file_put_contents = true;
        self::$is_resource = true;
        self::$php_sapi_name = $isCli ? 'cli' : 'not_cli';

        $uploadedFile->moveTo($path);
        $this->assertTrue(true);
    }

    /**
     * Provider. Gives filesystem error cases based on
     * - cli / not cli environment
     * - StreamInterface object with / without uri metadata
     * @return array
     */
    public function providerFsWorkingCases(): array
    {
        return [
            'cli + uri' => [true, true],
            'cli + no uri' => [true, false],
            'not cli + uri' => [false, true],
            'not cli + no uri' => [false, false],
            'cli + no uri + fopen fail' => [false, false],
        ];
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

use IngeniozIT\Http\Message\Tests\UploadedFileTest;

function rename(string $oldname, string $newname)
{
    return UploadedFileTest::$rename ?? \rename($oldname, $newname);
}

function is_uploaded_file(string $filename)
{
    return UploadedFileTest::$is_uploaded_file ?? \is_uploaded_file($filename);
}

function move_uploaded_file(string $filename, string $destination)
{
    return UploadedFileTest::$move_uploaded_file ?? \move_uploaded_file($filename, $destination);
}

function file_put_contents($filename, $data)
{
    return UploadedFileTest::$file_put_contents ?? \file_put_contents($filename, $data);
}

function fopen(string $path, $mode)
{
    return UploadedFileTest::$fopen ?? \fopen($path, $mode);
}

function realpath(string $path)
{
    return UploadedFileTest::$realpath ?? \realpath($path);
}

function php_sapi_name()
{
    return UploadedFileTest::$php_sapi_name ?? \php_sapi_name();
}

function is_resource($var)
{
    return UploadedFileTest::$is_resource ?? \is_resource($var);
}
