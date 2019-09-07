<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Tests\RequestTest;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\ServerRequest
 */
class ServerRequestTest extends RequestTest
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get a new ServerRequestInterface instance.
     *
     * @param  array         (optional) $headers         (optional) HTTP headers.
     * @param  string       (optional)  $protocolVersion (optional) HTTP procol version.
     * @param  string       (optional)  $method          (optional) HTTP method.
     * @param  UriInterface (optional)  $uri             (optional) Uri.
     * @return ServerRequestInterface
     */
    protected function getMessage(
        array $headers = [],
        ?string $protocolVersion = null,
        ?string $method = null,
        $uri = null,
        ?array $serverParams = null,
        ?array $cookieParams = null,
        ?array $queryParams = null,
        ?array $uploadedFiles = null
    ) {
        $mockStreamInterface = $this->getMockStream();

        if ($uploadedFiles !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion, $method ?? 'POST', $uri, $serverParams ?? [], $cookieParams ?? [], $queryParams ?? [], $uploadedFiles);
        } elseif ($queryParams !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion, $method ?? 'POST', $uri, $serverParams ?? [], $cookieParams ?? [], $queryParams);
        } elseif ($cookieParams !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion, $method ?? 'POST', $uri, $serverParams ?? [], $cookieParams);
        } elseif ($serverParams !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion, $method ?? 'POST', $uri, $serverParams);
        } elseif ($uri !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion, $method ?? 'POST', $uri);
        } elseif ($method !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion, $method ?? 'POST');
        } elseif ($protocolVersion !== null) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers, $protocolVersion);
        } elseif ($headers !== []) {
            return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface, $headers);
        }

        return new \IngeniozIT\Http\Message\ServerRequest($mockStreamInterface);
    }

    /**
     * Get a new ServerRequestInterface instance.
     *
     * @param  ?string $method (optional) HTTP method.
     * @param  ?string $uri    (optional) Uri.
     * @param  ?string $host   (optional) Return of $uri->getHost().
     * @return ServerRequestInterface
     */
    protected function getRequest($method = null, $uri = null, $host = null)
    {
        if ($uri !== null) {
            $mockUriInterface = $this->getMockUri();
            $mockUriInterface->method('__toString')->willReturn($uri);

            if ($host !== null) {
                $mockUriInterface->method('getHost')->willReturn($host);
            }

            $uri = $mockUriInterface;
        }

        return $this->getMessage([], null, $method, $uri);
    }

    /**
     * Get a new ServerRequestInterface instance.
     */
    protected function getServerRequest(?array $serverParams = null, ?array $cookieParams = null, ?array $queryParams = null, ?array $uploadedFiles = null)
    {
        return $this->getMessage([], null, null, null, $serverParams, $cookieParams, $queryParams, $uploadedFiles);
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getMessage() and getRequest() return a ServerRequestInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(ServerRequestInterface::class, $this->getMessage(), 'getMessage does not give a ServerRequestInterface object.');
        $this->assertInstanceOf(ServerRequestInterface::class, $this->getRequest(), 'getRequest does not give a ServerRequestInterface object.');
    }

    // ========================================== //
    // Server Params                              //
    // ========================================== //

    /**
     * Retrieve server parameters.
     */
    public function testGetServerParamsDefault()
    {
        $serverRequest = $this->getServerRequest();

        $this->assertSame([], $serverRequest->getServerParams());
    }

    /**
     * Retrieve server parameters.
     */
    public function testGetServerParams()
    {
        $serverParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest($serverParams);

        $this->assertSame($serverParams, $serverRequest->getServerParams());
    }

    // ========================================== //
    // Cookie Params                              //
    // ========================================== //

    /**
     * Retrieve cookies.
     */
    public function testGetCookieParamsDefault()
    {
        $serverRequest = $this->getServerRequest();

        $this->assertSame([], $serverRequest->getCookieParams());
    }

    /**
     * Retrieve cookies.
     */
    public function testGetCookieParams()
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest(null, $cookieParams);

        $this->assertSame($cookieParams, $serverRequest->getCookieParams());
    }

    /**
     * Return an instance with the specified cookies.
     */
    public function testWithCookieParams()
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest();
        $serverRequest2 = $serverRequest->withCookieParams($cookieParams);
        $this->assertSame($cookieParams, $serverRequest2->getCookieParams());
    }

    /**
     * Return an instance with the specified cookies.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     */
    public function testWithCookieParamsReturnsNewInstance()
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest();
        $serverRequest2 = $serverRequest->withCookieParams($cookieParams);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified cookies.
     * If the cookieParams given is the same as the ServerRequest's cookieParams,
     * the same instance will be returned.
     */
    public function testWithCookieParamsReturnsSameInstanceOnSameValue()
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest(null, $cookieParams);
        $serverRequest2 = $serverRequest->withCookieParams($cookieParams);

        $this->assertSame($serverRequest, $serverRequest2);
    }

    // ========================================== //
    // Query Params                               //
    // ========================================== //

    /**
     * Retrieve query string arguments.
     */
    public function testGetQueryParamsDefault()
    {
        $serverRequest = $this->getServerRequest();

        $this->assertSame([], $serverRequest->getQueryParams());
    }

    /**
     * Retrieve query string arguments.
     */
    public function testGetQueryParams()
    {
        $queryParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest(null, null, $queryParams);

        $this->assertSame($queryParams, $serverRequest->getQueryParams());
    }

    /**
     * Return an instance with the specified query string arguments.
     */
    public function testWithQueryParams()
    {
        $cookieParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest();
        $serverRequest2 = $serverRequest->withQueryParams($cookieParams);
        $this->assertSame($cookieParams, $serverRequest2->getQueryParams());
    }

    /**
     * Return an instance with the specified query string arguments.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     */
    public function testWithQueryParamsReturnsNewInstance()
    {
        $queryParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest();
        $serverRequest2 = $serverRequest->withQueryParams($queryParams);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified query string arguments.
     * If the queryParams given is the same as the ServerRequest's queryParams,
     * the same instance will be returned.
     */
    public function testWithQueryParamsReturnsSameInstanceOnSameValue()
    {
        $queryParams = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3',
            'param4' => 'value4'
        ];
        $serverRequest = $this->getServerRequest(null, null, $queryParams);
        $serverRequest2 = $serverRequest->withQueryParams($queryParams);

        $this->assertSame($serverRequest, $serverRequest2);
    }

    // ========================================== //
    // Uploaded Files                             //
    // ========================================== //

    /**
     * Retrieve normalized file upload data.
     */
    public function testGetUploadedFilesDefault()
    {
        $serverRequest = $this->getServerRequest();

        $this->assertSame([], $serverRequest->getUploadedFiles());
    }

    /**
     * Retrieve normalized file upload data.
     */
    public function testGetUploadedFiles()
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getServerRequest(null, null, null, $uploadedFiles);

        $this->assertSame($uploadedFiles, $serverRequest->getUploadedFiles());
    }

    /**
     * Create a new instance with the specified uploaded files.
     */
    public function testWithUploadedFiles()
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getServerRequest();
        $serverRequest2 = $serverRequest->withUploadedFiles($uploadedFiles);
        $this->assertSame($uploadedFiles, $serverRequest2->getUploadedFiles());
    }

    /**
     * Create a new instance with the specified uploaded files.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     */
    public function testWithUploadedFilesReturnsNewInstance()
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getServerRequest();
        $serverRequest2 = $serverRequest->withUploadedFiles($uploadedFiles);

        $this->assertNotSame($serverRequest, $serverRequest2);
    }

    /**
     * Return an instance with the specified query string arguments.
     * If the uploadedFiles given is the same as the ServerRequest's uploadedFiles,
     * the same instance will be returned.
     */
    public function testWithUploadedFilesReturnsSameInstanceOnSameValue()
    {
        $uploadedFiles = [
            'param1' => $this->createMock(UploadedFileInterface::class),
            'param2' => $this->createMock(UploadedFileInterface::class),
            'param3' => $this->createMock(UploadedFileInterface::class),
            'param4' => $this->createMock(UploadedFileInterface::class)
        ];
        $serverRequest = $this->getServerRequest(null, null, null, $uploadedFiles);
        $serverRequest2 = $serverRequest->withUploadedFiles($uploadedFiles);

        $this->assertSame($serverRequest, $serverRequest2);
    }
}
