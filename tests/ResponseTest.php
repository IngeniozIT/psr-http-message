<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Tests;

use IngeniozIT\Http\Message\Tests\MessageTest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \IngeniozIT\Http\Message\Response
 */
class ResponseTest extends MessageTest
{
    // ========================================== //
    // Implementation specific                    //
    // ========================================== //

    /**
     * Get a new MessageInterface instance.
     *
     * @param array $headers Http headers.
     * @param ?string $protocolVersion Http procol version.
     * @return MessageInterface
     */
    protected function getMessage(array $headers = [], ?string $protocolVersion = null, int $statusCode = null, string $reasonPhrase = null)
    {
        /** @var StreamInterface $mockStreamInterface */
        $mockStreamInterface = $this->createMock(StreamInterface::class);

        if ($reasonPhrase !== null) {
            return new \IngeniozIT\Http\Message\Response($mockStreamInterface, $headers, $protocolVersion, $statusCode, $reasonPhrase);
        } elseif ($statusCode !== null) {
            return new \IngeniozIT\Http\Message\Response($mockStreamInterface, $headers, $protocolVersion, $statusCode);
        } elseif ($protocolVersion !== null) {
            return new \IngeniozIT\Http\Message\Response($mockStreamInterface, $headers, $protocolVersion);
        } elseif ($headers !== []) {
            return new \IngeniozIT\Http\Message\Response($mockStreamInterface, $headers);
        }

        return new \IngeniozIT\Http\Message\Response($mockStreamInterface);
    }

    protected function getResponse(int $statusCode = null, string $reasonPhrase = null)
    {
        /** @var StreamInterface $mockStreamInterface */
        $mockStreamInterface = $this->createMock(StreamInterface::class);

        if ($reasonPhrase !== null) {
            return $this->getMessage($statusCode, $reasonPhrase);
        } elseif ($statusCode !== null) {
            return $this->getMessage($statusCode);
        }

        return $this->getMessage();
    }

    // ========================================== //
    // Constructor                                //
    // ========================================== //

    /**
     * Does getMessage() and getResponse() return a ResponseInterface ?
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(ResponseInterface::class, $this->getMessage(), 'getMessage does not give a RequestInterface object.');
        $this->assertInstanceOf(ResponseInterface::class, $this->getResponse(), 'getResponse does not give a RequestInterface object.');
    }
}
