<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UploadedFileInterface,
    UriFactoryInterface
};
use IngeniozIT\Http\Message\{ServerRequestFactory, StreamFactory, UploadedFileFactory, UriFactory};
use InvalidArgumentException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ServerRequestFactoryTest extends TestCase
{
    use WithTempFiles;

    protected function createFactory(
        StreamFactoryInterface $streamFactory = new StreamFactory(),
        UriFactoryInterface $uriFactory = new UriFactory(),
        UploadedFileFactoryInterface $uploadedFileFactory = new UploadedFileFactory(new StreamFactory()),
    ): ServerRequestFactory {
        return new ServerRequestFactory($streamFactory, $uriFactory, $uploadedFileFactory);
    }

    public function testIsAPsrRequestFactory(): void
    {
        $factory = $this->createFactory();

        self::assertInstanceOf(ServerRequestFactoryInterface::class, $factory);
    }

    public function testCanCreateARequestFromAUri(): void
    {
        $factory = $this->createFactory();
        $uri = (new UriFactory())->createUri('http://example.com');

        $request = $factory->createServerRequest('POST', $uri, ['foo' => 'bar']);

        self::assertEquals('http://example.com', $request->getUri());
        self::assertEquals(['foo' => 'bar'], $request->getServerParams());
        self::assertEquals('POST', $request->getMethod());
    }

    public function testCanCreateARequestFromAStringUri(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequest('PUT', 'http://example2.com');

        self::assertEquals('http://example2.com', $request->getUri());
        self::assertEquals('PUT', $request->getMethod());
    }

    public function testCannotCreateARequestWithAnInvalidMethod(): void
    {
        $factory = $this->createFactory();

        self::expectException(InvalidArgumentException::class);
        $factory->createServerRequest('INVALID_METHOD', 'http://example2.com');
    }

    public function testCanCreateARequestFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([]);

        self::assertEquals('1.1', $request->getProtocolVersion());
        self::assertEquals([], $request->getHeaders());
        self::assertEquals('', (string) $request->getBody());
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('', (string) $request->getUri());
        self::assertEquals([], $request->getServerParams());
        self::assertEquals([], $request->getCookieParams());
        self::assertEquals([], $request->getQueryParams());
        self::assertEquals([], $request->getUploadedFiles());
        self::assertEquals(null, $request->getParsedBody());
        self::assertEquals([], $request->getAttributes());
    }

    public function testExtractsProtocolVersionFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_SERVER' => [
                'SERVER_PROTOCOL' => 'HTTP/2',
            ],
        ]);

        self::assertEquals('2', $request->getProtocolVersion());
    }

    /**
     * @param array<string, string> $params
     * @param array<string, array<string, string>> $expectedHeaders
     * @dataProvider providerHeadersFromGlobals
     */
    public function testExtractsHeadersFromGlobals(array $params, array $expectedHeaders): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_SERVER' => $params,
        ]);

        self::assertEquals($expectedHeaders, $request->getHeaders());
    }

    /**
     * @return array<string, array{params: array<string, string>, expectedHeaders: array<string, string[]>}>
     */
    public static function providerHeadersFromGlobals(): array
    {
        return [
            'normal headers' => [
                'params' => [
                    'HTTP_HOST' => 'example.com',
                    'HTTP_USER_AGENT' => 'PHPUnit',
                ],
                'expectedHeaders' => [
                    'HOST' => ['example.com'],
                    'USER-AGENT' => ['PHPUnit'],
                ],
            ],
            'filters non-header values' => [
                'params' => [
                    'FOO' => 'BAR',
                    'HTTP_HOST' => 'example.com',
                ],
                'expectedHeaders' => [
                    'HOST' => ['example.com'],
                ],
            ],
            'handles headers with multiple values' => [
                'params' => [
                    'HTTP_FOO' => 'foo, bar, baz',
                ],
                'expectedHeaders' => [
                    'FOO' => ['foo', 'bar', 'baz'],
                ],
            ],
        ];
    }

    public function testExtractsBodyFromInput(): void
    {
        $streamFactory = $this->createStreamFactory(
            fn(MockObject $mock) => $mock->expects(self::once())
                ->method('createStreamFromFile')
                ->with('php://input')
                ->willReturn((new StreamFactory())->createStream('{body: "content"}')),
        );

        $factory = $this->createFactory(streamFactory: $streamFactory);
        $request = $factory->createServerRequestFromGlobals([]);

        self::assertEquals('{body: "content"}', (string) $request->getBody());
    }

    public function testExtractsHttpMethodFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_SERVER' => [
                'REQUEST_METHOD' => 'PUT',
            ],
        ]);

        self::assertEquals('PUT', $request->getMethod());
    }

    public function testExtractsRequestTargetFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_SERVER' => [
                'REQUEST_URI' => '/foo/bar?bar=baz',
            ],
        ]);

        self::assertEquals('/foo/bar?bar=baz', $request->getRequestTarget());
    }

    public function testExtractsUriFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_SERVER' => [
                'HTTP_HOST' => 'www.example.com:9999',
                'REQUEST_URI' => '/foo/bar?bar=baz',
            ],
        ]);

        self::assertEquals('//www.example.com:9999/foo/bar?bar=baz', (string) $request->getUri());
    }

    public function testExtractsServerParamsFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_SERVER' => [
                'FOO' => 'BAR',
                'HTTP_HOST' => 'www.example.com:9999',
            ],
        ]);

        self::assertEquals([
            'FOO' => 'BAR',
            'HTTP_HOST' => 'www.example.com:9999',
        ], $request->getServerParams());
    }

    /**
     * @param array<string, string> $server
     * @param array<string, string> $cookies
     * @param array<string, string> $expectedCookies
     * @dataProvider providerQueryParams
     */
    public function testExtractsCookieParams(array $server, array $cookies, array $expectedCookies): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_COOKIE' => $cookies,
            '_SERVER' => $server,
        ]);

        self::assertEquals($expectedCookies, $request->getCookieParams());
    }

    /**
     * @return array<string, array{server: array<string, string>, cookies: array<string, string>, expectedCookies: array<string, string>}>
     */
    public static function providerQueryParams(): array
    {
        return [
            'from cookies' => [
                'server' => [],
                'cookies' => [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
                'expectedCookies' => [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
            ],
            'from cookie header' => [
                'server' => [
                    'HTTP_COOKIE' => 'foo=bar; bar = baz=baz ',
                ],
                'cookies' => [],
                'expectedCookies' => [
                    'foo' => 'bar',
                    'bar' => 'baz=baz',
                ],
            ],
            'cookie header takes precedence over cookies' => [
                'server' => [
                    'HTTP_COOKIE' => 'foo=bar',
                ],
                'cookies' => [
                    'foo' => 'baz',
                ],
                'expectedCookies' => [
                    'foo' => 'bar',
                ],
            ],
        ];
    }

    public function testExtractsQueryParamsFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_GET' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ]);

        self::assertEquals([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $request->getQueryParams());
    }

    /**
     * @param array<string, array<string, mixed>> $files
     * @param mixed[] $expectedResult
     * @dataProvider providerUploadedFiles
     */
    public function testExtractsUploadedFilesFromGlobals(array $files, callable $callback, array $expectedResult): void
    {
        $streamFactory = $this->createStreamFactory(
            fn(MockObject $mock) => $mock->expects(self::any())
                ->method('createStreamFromFile')
                ->willReturnCallback(fn (string $file) => (new StreamFactory())->createStream($file)),
        );
        $uploadedFileFactory = new UploadedFileFactory($streamFactory);

        $factory = $this->createFactory(streamFactory: $streamFactory, uploadedFileFactory: $uploadedFileFactory);
        $request = $factory->createServerRequestFromGlobals(['_FILES' => $files]);
        $uploadedFiles = $request->getUploadedFiles();
        $result = $callback($uploadedFiles);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return array<string, array{files: array<string, mixed>, callback: callable, expectedResult: mixed[]}>
     */
    public static function providerUploadedFiles(): array
    {
        return [
            'one file' => [
                'files' => [
                    'avatar' => [
                        'tmp_name' => 'phpUxcOty',
                        'name' => 'my-avatar.png',
                        'size' => 90996,
                        'type' => 'image/png',
                        'error' => 1,
                    ],
                ],
                'callback' => fn(array $uploadedFiles) => self::extractUploadedFileData($uploadedFiles['avatar']),
                'expectedResult' => [
                    'content' => 'phpUxcOty',
                    'name' => 'my-avatar.png',
                    'size' => 90996,
                    'type' => 'image/png',
                    'error' => 1,
                ],
            ],
            'one file with incomplete data' => [
                'files' => [
                    'avatar' => [
                        'tmp_name' => 'phpUxcOty',
                    ],
                ],
                'callback' => fn(array $uploadedFiles) => self::extractUploadedFileData($uploadedFiles['avatar']),
                'expectedResult' => [
                    'content' => 'phpUxcOty',
                    'name' => null,
                    'size' => null,
                    'type' => null,
                    'error' => 0,
                ],
            ],
            'one nested file' => [
                'files' => [
                    'my-form' => [
                        'name' => ['details' => ['avatar' => 'my-avatar.png']],
                        'type' => ['details' => ['avatar' => 'image/png']],
                        'tmp_name' => ['details' => ['avatar' => 'phpmFLrzD']],
                        'error' => ['details' => ['avatar' => 0]],
                        'size' => ['details' => ['avatar' => 90996]],
                    ],
                ],
                'callback' => fn(array $uploadedFiles) => self::extractUploadedFileData($uploadedFiles['my-form']['details']['avatar']),
                'expectedResult' => [
                    'content' => 'phpmFLrzD',
                    'name' => 'my-avatar.png',
                    'size' => 90996,
                    'type' => 'image/png',
                    'error' => 0,
                ],
            ],
            'several nested files' => [
                'files' => [
                    'my-form' => [
                        'name' => ['avatars' => ['my-avatar.png', 'my-avatar2.png', 'my-avatar3.png']],
                        'type' => ['avatars' => ['image/png', 'image/png', 'image/png']],
                        'tmp_name' => ['avatars' => ['phpmFLrzD', 'phpV2pBil', 'php8RUG8v']],
                        'error' => ['avatars' => [0, 0, 0]],
                        'size' => ['avatars' => [90996, 90996, 90996]],
                    ],
                ],
                'callback' => fn(array $uploadedFiles) => [
                    self::extractUploadedFileData($uploadedFiles['my-form']['avatars'][0]),
                    self::extractUploadedFileData($uploadedFiles['my-form']['avatars'][1]),
                    self::extractUploadedFileData($uploadedFiles['my-form']['avatars'][2]),
                ],
                'expectedResult' => [
                    [
                        'content' => 'phpmFLrzD',
                        'name' => 'my-avatar.png',
                        'size' => 90996,
                        'type' => 'image/png',
                        'error' => 0,
                    ],
                    [
                        'content' => 'phpV2pBil',
                        'name' => 'my-avatar2.png',
                        'size' => 90996,
                        'type' => 'image/png',
                        'error' => 0,
                    ],
                    [
                        'content' => 'php8RUG8v',
                        'name' => 'my-avatar3.png',
                        'size' => 90996,
                        'type' => 'image/png',
                        'error' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractUploadedFileData(UploadedFileInterface $file): array
    {
        return [
            'content' => (string) $file->getStream(),
            'name' => $file->getClientFilename(),
            'size' => $file->getSize(),
            'type' => $file->getClientMediaType(),
            'error' => $file->getError(),
        ];
    }

    public function testExtractsParsedBodyFromGlobals(): void
    {
        $factory = $this->createFactory();

        $request = $factory->createServerRequestFromGlobals([
            '_POST' => [
                'foo' => 'bar',
                'bar' => 'baz',
            ],
        ]);

        self::assertEquals([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $request->getParsedBody());
    }

    /**
     * @phan-suppress PhanTypeMismatchReturn
     */
    private function createStreamFactory(Closure $callback): StreamFactoryInterface
    {
        /** @var StreamFactoryInterface&MockObject $factory */
        $factory = $this->createMock(StreamFactoryInterface::class);
        $callback($factory);
        return $factory;
    }
}
