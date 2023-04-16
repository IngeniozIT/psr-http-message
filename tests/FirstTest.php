<?php

declare(strict_types=1);

namespace IngeniozIT\Message\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Message\HelloWorld;

class FirstTest extends TestCase
{
    public function testHelloWorld(): void
    {
        $hello = new HelloWorld();

        $message = $hello->sayHello();

        self::assertSame('Hello World!', $message);
    }
}
