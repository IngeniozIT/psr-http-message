<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use Psr\Http\Message\ServerRequestInterface;
use IngeniozIT\Http\Message\{
    StreamFactory,
    UriFactory,
    ServerRequest,
    UploadedFileFactory,
};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use IngeniozIT\Http\Message\ValueObject\Request\Method;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ServerRequestTest extends RequestTest
{
    /**
     * @param array<string, mixed> $serverParams
     */
    protected function getMessage(array $serverParams = []): ServerRequestInterface
    {
        $streamFactory = new StreamFactory();
        $uriFactory = new UriFactory();
        return new ServerRequest(
            protocolVersion: '1.1',
            headers: new Headers([]),
            body: $streamFactory->createStream(),
            method: Method::GET,
            requestTarget: '',
            uri: $uriFactory->createUri(),
            serverParams: $serverParams,
            cookieParams: [],
            queryParams: [],
            uploadedFiles: [],
            parsedBody: null,
            attributes: [],
        );
    }

    public function testIsAPsrServerRequest(): void
    {
        $request = $this->getMessage();

        self::assertInstanceOf(ServerRequestInterface::class, $request);
    }

    public function testHasServerParams(): void
    {
        $request = $this->getMessage(['foo' => 'bar']);

        $serverParams = $request->getServerParams();

        self::assertEquals(['foo' => 'bar'], $serverParams);
    }

    public function testHasCookieParams(): void
    {
        $request = $this->getMessage()
            ->withCookieParams(['foo' => 'bar']);

        $cookies = $request->getCookieParams();

        self::assertEquals(['foo' => 'bar'], $cookies);
    }

    public function testHasQueryParams(): void
    {
        $request = $this->getMessage()
            ->withQueryParams(['foo' => 'bar']);

        $query = $request->getQueryParams();

        self::assertEquals(['foo' => 'bar'], $query);
    }

    public function testHasUploadedFiles(): void
    {
        $streamFactory = new StreamFactory();
        $uploadedFileFactory = new UploadedFileFactory($streamFactory);
        $files = [
            'file1' => $uploadedFileFactory->createUploadedFile($streamFactory->createStream()),
            'file2' => $uploadedFileFactory->createUploadedFile($streamFactory->createStream()),
        ];
        $request = $this->getMessage()
            ->withUploadedFiles($files);

        $uploadedFiles = $request->getUploadedFiles();

        self::assertSame($files, $uploadedFiles);
    }

    public function testHasParsedBody(): void
    {
        $request = $this->getMessage()
            ->withParsedBody(['foo' => 'bar']);

        $body = $request->getParsedBody();

        self::assertEquals(['foo' => 'bar'], $body);
    }

    public function testHasAttributes(): void
    {
        $request = $this->getMessage()
            ->withAttribute('foo', 'bar')
            ->withAttribute('bar', 'baz');
        $attribute = $request->getAttribute('foo');
        $attributes = $request->getAttributes();

        self::assertEquals('bar', $attribute);
        self::assertEquals(['foo' => 'bar', 'bar' => 'baz'], $attributes);
    }

    public function testCanGetAttributeWithDefaultValue(): void
    {
        $request = $this->getMessage();

        $attribute = $request->getAttribute('foo');

        self::assertEquals(null, $attribute);
    }

    public function testCanGetAttributeWithCustomDefaultValue(): void
    {
        $request = $this->getMessage();

        $attribute = $request->getAttribute('foo', 'bar');

        self::assertEquals('bar', $attribute);
    }

    public function testCanRemoveAttribute(): void
    {
        $request = $this->getMessage()
            ->withAttribute('foo', 'bar')
            ->withoutAttribute('foo');

        $attribute = $request->getAttribute('foo');
        $attributes = $request->getAttributes();

        self::assertEquals(null, $attribute);
        self::assertEquals([], $attributes);
    }

    public function testUsesTheSameInstanceWhenContentDoesNotChange(): void
    {
        $streamFactory = new StreamFactory();
        $uploadedFileFactory = new UploadedFileFactory($streamFactory);
        $files = [
            'file1' => $uploadedFileFactory->createUploadedFile($streamFactory->createStream()),
            'file2' => $uploadedFileFactory->createUploadedFile($streamFactory->createStream()),
        ];
        $stream = $streamFactory->createStream('test');
        $uri = (new UriFactory())->createUri('http://example.com/foo/bar');
        $request = $this->getMessage()
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withBody($stream)
            ->withMethod('PUT')
            ->withRequestTarget('/foo/bar')
            ->withUri($uri)
            ->withCookieParams(['foo' => 'bar'])
            ->withQueryParams(['foo' => 'bar'])
            ->withUploadedFiles($files)
            ->withParsedBody(['foo' => 'bar'])
            ->withAttribute('foo', 'bar');

        $request2 = $request
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withAddedHeader('X-Test', 'test')
            ->withoutHeader('X-Test2')
            ->withBody($stream)
            ->withMethod('PUT')
            ->withRequestTarget('/foo/bar')
            ->withUri($uri)
            ->withCookieParams(['foo' => 'bar'])
            ->withQueryParams(['foo' => 'bar'])
            ->withUploadedFiles($files)
            ->withParsedBody(['foo' => 'bar'])
            ->withAttribute('foo', 'bar')
            ->withoutAttribute('baz');

        self::assertSame($request, $request2);
    }
}
