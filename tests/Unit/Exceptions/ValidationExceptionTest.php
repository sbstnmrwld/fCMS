<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Exceptions;

use FCMS\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für ValidationException
 */
class ValidationExceptionTest extends TestCase
{
    public function testCanBeCreatedWithMessage(): void
    {
        $exception = new ValidationException('Test error');

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertEmpty($exception->getErrors());
    }

    public function testCanBeCreatedWithErrors(): void
    {
        $errors = [
            'title' => 'Title is required',
            'email' => 'Invalid email'
        ];

        $exception = new ValidationException('Validation failed', $errors);

        $this->assertSame($errors, $exception->getErrors());
    }

    public function testHasErrorReturnsTrueForExistingField(): void
    {
        $errors = ['title' => 'Title is required'];
        $exception = new ValidationException('Error', $errors);

        $this->assertTrue($exception->hasError('title'));
        $this->assertFalse($exception->hasError('email'));
    }

    public function testGetErrorReturnsFieldError(): void
    {
        $errors = ['title' => 'Title is required'];
        $exception = new ValidationException('Error', $errors);

        $this->assertSame('Title is required', $exception->getError('title'));
        $this->assertNull($exception->getError('email'));
    }

    public function testWithErrorsFactoryMethod(): void
    {
        $errors = [
            'title' => 'Title error',
            'email' => 'Email error'
        ];

        $exception = ValidationException::withErrors($errors);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame($errors, $exception->getErrors());
        $this->assertStringContainsString('title, email', $exception->getMessage());
    }

    public function testIsInstanceOfInvalidArgumentException(): void
    {
        $exception = new ValidationException('Test');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}
