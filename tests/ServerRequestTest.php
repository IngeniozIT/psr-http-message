<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Tests\RequestTest;
use Psr\Http\Message\{ServerRequestInterface, UploadedFileInterface};

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\ServerRequest
 */
class ServerRequestTest extends RequestTest
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /** @var string $className Class name of the tested class */
    protected string $className = \IngeniozIT\Http\Message\ServerRequest::class;

    /**
     * Get a new ServerRequestInterface instance.
     *
     * @param array<mixed> $headers (optional) HTTP headers.
     */
    protected function getMessage(array $headers = []): ServerRequestInterface
    {
        return new $this->className($this->getMockStream(), $headers);
    }

    // ========================================== //
    // Server Params                              //
    // ========================================== //

    /**
     * Retrieve server parameters.
     */
    public function testGetServerParamsDefault(): void
    {
        $serverRequest = $this->getMessage();

        $this->assertSame([], $serverRequest->getServerParams());
    }

    /**
     * Retrieve server parameters.
     */
    public function testGetServerParams(): void
    {
        $serverParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage($serverParams);

        $this->assertSame($serverParams, $serverRequest->getServerParams());
    }

    // ========================================== //
    // Cookie Params                              //
    // ========================================== //

    /**
     * Retrieve cookies.
     */
    public function testGetCookieParamsDefault(): void
    {
        $serverRequest = $this->getMessage();

        $this->assertSame([], $serverRequest->getCookieParams());
    }

    /**
     * Retrieve cookies.
     */
    public function testGetCookieParams(): void
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage()->withCookieParams($cookieParams);

        $this->assertSame($cookieParams, $serverRequest->getCookieParams());
    }

    /**
     * Return an instance with the specified cookies.
     */
    public function testWithCookieParams(): void
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withCookieParams($cookieParams);
        $this->assertSame($cookieParams, $serverRequest2->getCookieParams());
    }

    /**
     * Return an instance with the specified cookies.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     */
    public function testWithCookieParamsReturnsNewInstance(): void
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withCookieParams($cookieParams);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified cookies.
     * If the cookieParams given is the same as the ServerRequest's cookieParams,
     * the same instance will be returned.
     */
    public function testWithCookieParamsReturnsSameInstanceOnSameValue(): void
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage()->withCookieParams($cookieParams);
        $serverRequest2 = $serverRequest->withCookieParams($cookieParams);

        $this->assertSame($serverRequest, $serverRequest2);
    }

    // ========================================== //
    // Query Params                               //
    // ========================================== //

    /**
     * Retrieve query string arguments.
     */
    public function testGetQueryParamsDefault(): void
    {
        $serverRequest = $this->getMessage();

        $this->assertSame([], $serverRequest->getQueryParams());
    }

    /**
     * Retrieve query string arguments.
     */
    public function testGetQueryParams(): void
    {
        $queryParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];

        $serverRequest = $this->getMessage()->withQueryParams($queryParams);

        $this->assertSame($queryParams, $serverRequest->getQueryParams());
    }

    /**
     * Return an instance with the specified query string arguments.
     */
    public function testWithQueryParams(): void
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withQueryParams($cookieParams);
        $this->assertSame($cookieParams, $serverRequest2->getQueryParams());
    }

    /**
     * Return an instance with the specified query string arguments.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     */
    public function testWithQueryParamsReturnsNewInstance(): void
    {
        $queryParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withQueryParams($queryParams);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified query string arguments.
     * If the queryParams given is the same as the ServerRequest's queryParams,
     * the same instance will be returned.
     */
    public function testWithQueryParamsReturnsSameInstanceOnSameValue(): void
    {
        $queryParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getMessage()->withQueryParams($queryParams);
        $serverRequest2 = $serverRequest->withQueryParams($queryParams);

        $this->assertSame($serverRequest, $serverRequest2);
    }

    // ========================================== //
    // Uploaded Files                             //
    // ========================================== //

    /**
     * Retrieve normalized file upload data.
     */
    public function testGetUploadedFilesDefault(): void
    {
        $serverRequest = $this->getMessage();

        $this->assertSame([], $serverRequest->getUploadedFiles());
    }

    /**
     * Retrieve normalized file upload data.
     */
    public function testGetUploadedFiles(): void
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getMessage()->withUploadedFiles($uploadedFiles);

        $this->assertSame($uploadedFiles, $serverRequest->getUploadedFiles());
    }

    /**
     * Create a new instance with the specified uploaded files.
     */
    public function testWithUploadedFiles(): void
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withUploadedFiles($uploadedFiles);
        $this->assertSame($uploadedFiles, $serverRequest2->getUploadedFiles());
    }

    /**
     * Create a new instance with the specified uploaded files.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     */
    public function testWithUploadedFilesReturnsNewInstance(): void
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withUploadedFiles($uploadedFiles);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified query string arguments.
     * If the uploadedFiles given is the same as the ServerRequest's uploadedFiles,
     * the same instance will be returned.
     */
    public function testWithUploadedFilesReturnsSameInstanceOnSameValue(): void
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];

        $serverRequest = $this->getMessage()->withUploadedFiles($uploadedFiles);
        $serverRequest2 = $serverRequest->withUploadedFiles($uploadedFiles);

        $this->assertSame($serverRequest, $serverRequest2);
    }

    // ========================================== //
    // Parsed Body                                //
    // ========================================== //

    /**
     * Retrieve any parameters provided in the request body.
     */
    public function testGetParsedBodyDefault(): void
    {
        $serverRequest = $this->getMessage();

        $this->assertNull($serverRequest->getParsedBody());
    }

    /**
     * Return an instance with the specified body parameters.
     */
    public function testWithParsedBody(): void
    {
        $parsedBody = [
            'foo' => 42,
            'bar' => ['foobar'],
            'baz' => null,
        ];

        $serverRequest = $this->getMessage()->withParsedBody($parsedBody);

        $this->assertSame($parsedBody, $serverRequest->getParsedBody());
    }

    /**
     * Return an instance with the specified body parameters.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     */
    public function testWithParsedBodyReturnsNewInstance(): void
    {
        $parsedBody = [
            'foo' => 42,
            'bar' => ['foobar'],
            'baz' => null,
        ];
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withParsedBody($parsedBody);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified body parameters.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     */
    public function testWithParsedBodyReturnsSameInstanceOnSameValue(): void
    {
        $parsedBody = [
            'foo' => 42,
            'bar' => ['foobar'],
            'baz' => null,
        ];

        $serverRequest = $this->getMessage()->withParsedBody($parsedBody);
        $serverRequest2 = $serverRequest->withParsedBody($parsedBody);

        $this->assertSame($serverRequest, $serverRequest2);
    }

    // ========================================== //
    // Attributes                                 //
    // ========================================== //

    /**
     * Retrieve attributes derived from the request.
     */
    public function testGetAttributesDefault(): void
    {
        $serverRequest = $this->getMessage();

        $this->assertSame([], $serverRequest->getAttributes());
    }

    /**
     * Retrieve attributes derived from the request.
     */
    public function testGetAttributes(): void
    {
        $serverRequest = $this
            ->getMessage()
            ->withAttribute('foo', 'bar baz')
            ->withAttribute('baz?', ['this', 'is', 'baz!'])
            ->withAttribute('that one is null', null);

        $this->assertSame(
            [
                'foo' => 'bar baz',
                'baz?' => ['this', 'is', 'baz!'],
                'that one is null' => null
            ],
            $serverRequest->getAttributes()
        );
    }

    /**
     * Return an instance with the specified derived request attribute.
     */
    public function testWithAttribute(): void
    {
        $serverRequest = $this->getMessage();

        $serverRequest = $serverRequest->withAttribute('foo', 'bar baz');

        $this->assertSame('bar baz', $serverRequest->getAttribute('foo'));
    }

    /**
     * Return an instance with the specified derived request attribute.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     */
    public function testWithAttributeReturnsNewInstance(): void
    {
        $serverRequest = $this->getMessage();
        $serverRequest2 = $serverRequest->withAttribute('foo', 'bar baz');

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified derived request attribute.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     */
    public function testWithAttributeReturnsSameInstanceOnSameValue(): void
    {
        $serverRequest = $this->getMessage()->withAttribute('foo', 'bar baz');
        $serverRequest2 = $serverRequest->withAttribute('foo', 'bar baz');

        $this->assertSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     */
    public function testWithoutAttribute(): void
    {
        $serverRequest = $this->getMessage()->withAttribute('foo', 'bar baz');

        $serverRequest2 = $serverRequest->withoutAttribute('foo');

        $this->assertNull($serverRequest2->getAttribute('foo'));
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     */
    public function testWithoutAttributeReturnsNewInstance(): void
    {
        $serverRequest = $this->getMessage()->withAttribute('foo', 'bar baz');

        $serverRequest2 = $serverRequest->withoutAttribute('foo');

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     */
    public function testWithoutAttributeReturnsSameInstanceOnSameValue(): void
    {
        $serverRequest = $this->getMessage();

        $serverRequest2 = $serverRequest->withoutAttribute('foo');

        $this->assertSame($serverRequest, $serverRequest2);
    }
}
