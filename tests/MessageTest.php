<?php

declare(strict_types=1);

namespace IngeniozIT\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use IngeniozIT\Http\Message\{StreamFactory, Message};
use IngeniozIT\Http\Message\ValueObject\Message\Headers;
use InvalidArgumentException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MessageTest extends TestCase
{
    protected function getMessage(): MessageInterface
    {
        $streamFactory = new StreamFactory();
        return new Message(
            '1.1',
            new Headers([]),
            $streamFactory->createStream(),
        );
    }

    public function testIsAPsrMessage(): void
    {
        $stream = $this->getMessage();

        self::assertInstanceOf(MessageInterface::class, $stream);
    }

    public function testHasAProtocolVersion(): void
    {
        $stream = $this->getMessage()->withProtocolVersion('1.0');
        $protocolVersion = $stream->getProtocolVersion();

        self::assertEquals('1.0', $protocolVersion);
    }

    /**
     * @param string|string[] $initialValue
     * @param string[] $expectedValue
     * @dataProvider providerHeaders
     */
    public function testHasHeaders(
        string $initialName,
        string|array $initialValue,
        string $name,
        array $expectedValue,
        string $expectedLineValue,
    ): void {
        $stream = $this->getMessage()->withHeader($initialName, $initialValue);

        $hasInitialHeader = $stream->hasHeader($initialName);
        $hasHeader = $stream->hasHeader($name);
        $header = $stream->getHeader($name);
        $headerLine = $stream->getHeaderLine($name);
        $headers = $stream->getHeaders();

        self::assertTrue($hasInitialHeader);
        self::assertTrue($hasHeader);
        self::assertEquals($expectedValue, $header);
        self::assertEquals($expectedLineValue, $headerLine);
        self::assertEquals([$initialName => $expectedValue], $headers);
    }

    /**
     * @return array<string, array{initialName: string, initialValue: string|string[], name: string, expectedValue: string[], expectedLineValue: string}>
     */
    public static function providerHeaders(): array
    {
        return [
            'string value' => [
                'initialName' => 'X-Test',
                'initialValue' => 'test',
                'name' => 'X-Test',
                'expectedValue' => ['test'],
                'expectedLineValue' => 'test',
            ],
            'array value' => [
                'initialName' => 'X-Test',
                'initialValue' => ['test'],
                'name' => 'X-Test',
                'expectedValue' => ['test'],
                'expectedLineValue' => 'test',
            ],
            'multiple array values' => [
                'initialName' => 'X-Test',
                'initialValue' => ['test', 'test2'],
                'name' => 'X-Test',
                'expectedValue' => ['test', 'test2'],
                'expectedLineValue' => 'test,test2',
            ],
            'case insensitive name' => [
                'initialName' => 'X-Test',
                'initialValue' => 'test',
                'name' => 'x-test',
                'expectedValue' => ['test'],
                'expectedLineValue' => 'test',
            ],
        ];
    }

    public function testOverridingAHeaderOverridesItsName(): void
    {
        $stream = $this->getMessage()
            ->withHeader('X-Test', 'test')
            ->withHeader('X-Test2', 'test2')
            ->withHeader('x-test', 'test');
        $headers = $stream->getHeaders();

        self::assertEquals([
            'X-Test2' => ['test2'],
            'x-test' => ['test'],
        ], $headers);
    }

    public function testHeaderValuesDoNotKeepIndexes(): void
    {
        $stream = $this->getMessage()
            ->withHeader('X-Test', ['foo' => 'test', 'bar' => 'test2']);
        $header = $stream->getHeader('X-Test');

        self::assertEquals(['test', 'test2'], $header);
    }

    /**
     * @dataProvider providerInvalidHeaders
     */
    public function testHeaderNameAndValueMustNotBeInvalid(string $name, string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMessage()->withHeader($name, $value);
    }

    /**
     * @return array<string, array{name: string, value: string}>
     */
    public static function providerInvalidHeaders(): array
    {
        return [
            'empty name' => [
                'name' => '',
                'value' => 'test',
            ],
            'empty value' => [
                'name' => 'X-Test',
                'value' => '',
            ],
        ];
    }

    public function testCanAddAValueToAnExistingHeader(): void
    {
        $stream = $this->getMessage()
            ->withHeader('X-Test', 'test')
            ->withAddedHeader('X-Test', 'test2');
        $header = $stream->getHeaderLine('X-Test');

        self::assertEquals('test,test2', $header);
    }

    public function testExistingValuesDoNotGetAdded(): void
    {
        $stream = $this->getMessage()
            ->withHeader('X-Test', 'test')
            ->withAddedHeader('X-Test', ['test', 'test2']);
        $header = $stream->getHeaderLine('X-Test');

        self::assertEquals('test,test2', $header);
    }

    public function testCanRemoveAHeader(): void
    {
        $stream = $this->getMessage()
            ->withHeader('TEST1', 'test')
            ->withHeader('TEST2', 'test2')
            ->withoutHeader('TEST2');
        $hasHeader = $stream->hasHeader('TEST2');
        $header = $stream->getHeader('TEST2');
        $headers = $stream->getHeaders();

        self::assertFalse($hasHeader);
        self::assertEquals([], $header);
        self::assertEquals(['TEST1' => ['test']], $headers);
    }

    public function testHasABody(): void
    {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream('test');

        $message = $this->getMessage()->withBody($stream);

        self::assertSame($stream, $message->getBody());
    }

    public function testUsesTheSameInstanceWhenContentDoesNotChange(): void
    {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream('test');
        $message = $this->getMessage()
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withBody($stream);

        $message2 = $message
            ->withProtocolVersion('2.0')
            ->withHeader('X-Test', 'test')
            ->withAddedHeader('X-Test', 'test')
            ->withoutHeader('X-Test2')
            ->withBody($stream);

        self::assertSame($message, $message2);
    }
}
