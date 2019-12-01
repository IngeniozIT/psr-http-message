<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{UploadedFileInterface, StreamInterface};
use IngeniozIT\Http\Tests\Message\NativeFunctionsMocker;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\UploadedFile
 */
class UploadedFileTest extends TestCase
{
    /**
     * After each test, reset functions overrides.
     */
    protected function tearDown(): void
    {
        NativeFunctionsMocker::resetAll();
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
    protected function getStreamMock($methods = []): StreamInterface
    {
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
    protected function getUploadedFile(?int $size = null, ?int $error = null, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        $mockStream = $this->getStreamMock([
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
        $uploadedFile = $this->getUploadedFile();

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
        $uploadedFile = $this->getUploadedFile();

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
        NativeFunctionsMocker::makeFunctionReturn('realpath', false);

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
    public function testMoveToThrowsExceptionOnFilesystemError(bool $streamWithUri, array $functionsOverrides)
    {
        $uploadedFile = $this->getUploadedFile();
        $path = $this->getEmptyPath();
        $mockStream = $this->getStreamMock([
            'getContents' => 'foo bar baz !',
            'getMetadata' => $streamWithUri ? 'test_uri' : null,
        ]);
        $uploadedFile = new \IngeniozIT\Http\Message\UploadedFile($mockStream);
        $path = $this->getEmptyPath();

        NativeFunctionsMocker::makeFunctionsReturn($functionsOverrides);

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
            'steam with uri + php cli  + rename fail' => [true, ['php_sapi_name' => 'cli', 'rename' => false]],
            'steam with uri + php not cli  + is_uploaded_file fail' => [true, ['php_sapi_name' => 'not_cli', 'is_uploaded_file' => false]],
            'steam with uri + php not cli  + move_uploaded_file fail' => [true, ['php_sapi_name' => 'not_cli', 'move_uploaded_file' => false]],
            'steam without uri + fopen fail' => [false, ['fopen' => false]],
            'steam without uri + fwrite fail' => [false, ['fwrite' => false]],
            'steam without uri + fclose fail' => [false, ['fclose' => false]],
        ];
    }

    /**
     * Move the uploaded file to a new location.
     * @dataProvider providerFsWorkingCases
     */
    public function testMoveToWorksWithAllEnvs(bool $streamWithUri, array $functionsOverrides)
    {
        $uploadedFile = $this->getUploadedFile();
        $path = $this->getEmptyPath();
        $mockStream = $this->getStreamMock([
            'getContents' => 'foo bar baz !',
            'getMetadata' => $streamWithUri ? 'test_uri' : null,
        ]);
        $uploadedFile = new \IngeniozIT\Http\Message\UploadedFile($mockStream);
        $path = $this->getEmptyPath();
        NativeFunctionsMocker::makeFunctionsReturn($functionsOverrides);

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
            'steam with uri + php cli' => [true, ['php_sapi_name' => 'cli', 'rename' => true]],
            'steam with uri + php not cli' => [true, ['php_sapi_name' => 'not_cli', 'is_uploaded_file' => true, 'move_uploaded_file' => true]],
            'steam without uri' => [false, ['fopen' => fopen('php://temp', 'w')]],
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
        $uploadedFile = $this->getUploadedFile(42);

        $this->assertSame(42, $uploadedFile->getSize());
    }

    /**
     * Retrieve the file size.
     * return int|null The file size in bytes or null if unknown.
     */
    public function testGetSizeWithUnknownSize()
    {
        $uploadedFile = $this->getUploadedFile();

        $this->assertNull($uploadedFile->getSize());
    }

    /**
     * Retrieve the file size.
     * return int|null The file size in bytes or null if unknown.
     */
    public function testGetSizeFromStreamSize()
    {
        $mockStream = $this->getStreamMock([
            'getSize' => 84,
        ]);

        $uploadedFile = new \IngeniozIT\Http\Message\UploadedFile($mockStream);

        $this->assertSame(84, $uploadedFile->getSize());
    }

    // ========================================== //
    // Get Error                                  //
    // ========================================== //

    /**
     * Retrieve the error associated with the uploaded file.
     */
    public function testGetError()
    {
        $uploadedFile = $this->getUploadedFile(null, \UPLOAD_ERR_CANT_WRITE);

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
        $uploadedFile = $this->getUploadedFile(null, $error);

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
        $uploadedFile = $this->getUploadedFile(null, $error);
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

        $uploadedFile = $this->getUploadedFile(null, 0, 'fileName.test');

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

        $uploadedFile = $this->getUploadedFile(null, 0, null, 'MIME/TYPE');

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
