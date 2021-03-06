<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Tests\MessageTest;
use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Response
 */
class ResponseTest extends MessageTest
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /** @var string $className Class name of the tested class */
    protected string $className = \IngeniozIT\Http\Message\Response::class;

    /**
     * @var int Implementation's default HTTP status code.
     */
    protected $defaultStatusCode = 200;

    /**
     * @var string Implementation's default HTTP reason phrase.
     */
    protected $defaultReasonPhrase = 'OK';

    /**
     * Get an instance of the tested Response object.
     * @return ResponseInterface
     */
    protected function getResponse(): ResponseInterface
    {
        return $this->getMessage();
    }

    // ========================================== //
    // Defaults                                   //
    // ========================================== //

    /**
     * Check default status code and reason phrase.
     */
    public function testGetDefaults()
    {
        $response = $this->getResponse();

        $this->assertSame($this->defaultStatusCode, $response->getStatusCode());
        $this->assertSame($this->defaultReasonPhrase, $response->getReasonPhrase());
    }

    // ========================================== //
    // Status                                     //
    // ========================================== //

    /**
     * Return an instance with the specified status code
     */
    public function testWithStatus()
    {
        $response = $this->getResponse();

        $response2 = $response->withStatus(404);

        $this->assertSame(404, $response2->getStatusCode());
    }

    /**
     * Return an instance with the specified status code
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     */
    public function testWithBadStatus()
    {
        $response = $this->getResponse();

        $this->expectException(\InvalidArgumentException::class);
        $response2 = $response->withStatus(4040);
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     */
    public function testWithStatusAndReasonPhrase()
    {
        $response = $this->getResponse();

        $response2 = $response->withStatus(404, 'A reason phrase');

        $this->assertSame(404, $response2->getStatusCode());
        $this->assertSame('A reason phrase', $response2->getReasonPhrase());
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     */
    public function testWithStatusIsImmutable()
    {
        $response = $this->getResponse()->withStatus(200);
        $response2 = $response->withStatus(200);
        $response3 = $response->withStatus(404);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame(404, $response3->getStatusCode());

        $this->assertNotSame($response3, $response, 'Response status code is not immutable.');
        $this->assertSame($response, $response2, 'Response status code is badly immutable.');
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     */
    public function testWithStatusAndReasonPhraseIsImmutable()
    {
        $response = $this->getResponse()->withStatus(200, 'Reason phrase');
        $response2 = $response->withStatus(200, 'Reason phrase');
        $response3 = $response->withStatus(200, 'Another reason phrase');
        $response4 = $response->withStatus(404, 'Reason phrase');
        $response5 = $response->withStatus(404, 'Another reason phrase');

        $this->assertNotSame($response, $response3, 'Response status code is not immutable.');
        $this->assertNotSame($response, $response4, 'Response status code is not immutable.');
        $this->assertNotSame($response, $response5, 'Response status code is not immutable.');
        $this->assertSame($response, $response2, 'Response status code is badly immutable.');
    }
}
