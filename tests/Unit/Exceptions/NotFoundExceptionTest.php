<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Exceptions;

use FCMS\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für NotFoundException
 */
class NotFoundExceptionTest extends TestCase
{
    public function testCanBeCreatedWithMessage(): void
    {
        $exception = new NotFoundException('Resource not found');

        $this->assertSame('Resource not found', $exception->getMessage());
    }

    public function testPageFactoryMethod(): void
    {
        $exception = NotFoundException::page('my-slug');

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertStringContainsString('my-slug', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function testFileFactoryMethod(): void
    {
        $exception = NotFoundException::file('/path/to/file.txt');

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertStringContainsString('/path/to/file.txt', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new NotFoundException('Test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
