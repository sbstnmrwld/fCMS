<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Exceptions;

use FCMS\Exceptions\StorageException;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für StorageException
 */
class StorageExceptionTest extends TestCase
{
    public function testCanBeCreatedWithMessage(): void
    {
        $exception = new StorageException('Storage error');

        $this->assertSame('Storage error', $exception->getMessage());
    }

    public function testCannotWriteFactoryMethod(): void
    {
        $exception = StorageException::cannotWrite('/path/to/file.txt');

        $this->assertInstanceOf(StorageException::class, $exception);
        $this->assertStringContainsString('Cannot write', $exception->getMessage());
        $this->assertStringContainsString('/path/to/file.txt', $exception->getMessage());
    }

    public function testCannotWriteWithReason(): void
    {
        $exception = StorageException::cannotWrite('/path/to/file.txt', 'Permission denied');

        $this->assertStringContainsString('Permission denied', $exception->getMessage());
    }

    public function testCannotReadFactoryMethod(): void
    {
        $exception = StorageException::cannotRead('/path/to/file.txt');

        $this->assertInstanceOf(StorageException::class, $exception);
        $this->assertStringContainsString('Cannot read', $exception->getMessage());
        $this->assertStringContainsString('/path/to/file.txt', $exception->getMessage());
    }

    public function testCannotReadWithReason(): void
    {
        $exception = StorageException::cannotRead('/path/to/file.txt', 'File is locked');

        $this->assertStringContainsString('File is locked', $exception->getMessage());
    }

    public function testCannotDeleteFactoryMethod(): void
    {
        $exception = StorageException::cannotDelete('/path/to/file.txt');

        $this->assertInstanceOf(StorageException::class, $exception);
        $this->assertStringContainsString('Cannot delete', $exception->getMessage());
        $this->assertStringContainsString('/path/to/file.txt', $exception->getMessage());
    }

    public function testCannotDeleteWithReason(): void
    {
        $exception = StorageException::cannotDelete('/path/to/file.txt', 'File in use');

        $this->assertStringContainsString('File in use', $exception->getMessage());
    }

    public function testInvalidJsonFactoryMethod(): void
    {
        $exception = StorageException::invalidJson('/path/to/file.json');

        $this->assertInstanceOf(StorageException::class, $exception);
        $this->assertStringContainsString('invalid JSON', $exception->getMessage());
        $this->assertStringContainsString('/path/to/file.json', $exception->getMessage());
    }

    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new StorageException('Test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
