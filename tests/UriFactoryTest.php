<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Http\Message\UriFactory;
use Psr\Http\Message\UriFactoryInterface;
use InvalidArgumentException;

class UriFactoryTest extends TestCase
{
    public function testIsAPsrUriFactory(): void
    {
        $factory = new UriFactory();

        self::assertInstanceOf(UriFactoryInterface::class, $factory);
    }

    /**
     * @dataProvider providerUris
     */
    public function testCreatesUri(string $uriStr): void
    {
        $factory = new UriFactory();

        $uri = $factory->createUri($uriStr);

        self::assertEquals($uriStr, (string) $uri);
    }

    /**
     * @return array<string, array{uri: string}>
     */
    public static function providerUris(): array
    {
        return [
            'empty uri' => ['uri' => ''],
            'full uri' => ['uri' => 'http://user:pass@www.example.com:8080/test/path?foo=bar&bar=baz#fragment'],
        ];
    }

    public function testThrowsExceptionOnInvalidUri(): void
    {
        $factory = new UriFactory();

        self::expectException(InvalidArgumentException::class);
        $factory->createUri('http:///example.com');
    }
}
