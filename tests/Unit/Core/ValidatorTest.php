<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\Validator;
use FCMS\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Validator-Klasse
 */
class ValidatorTest extends TestCase
{
    // ========== String-Validierung ==========

    public function testStringAcceptsValidString(): void
    {
        $result = Validator::string('Hello World');
        $this->assertSame('Hello World', $result);
    }

    public function testStringRejectsNonString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value must be a string');
        Validator::string(123);
    }

    public function testStringEnforcesMinLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least 5 characters');
        Validator::string('Hi', minLength: 5);
    }

    public function testStringEnforcesMaxLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed 10 characters');
        Validator::string('This is a very long string', maxLength: 10);
    }

    public function testStringRejectsEmptyWhenNotAllowed(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot be empty');
        Validator::string('', allowEmpty: false);
    }

    public function testStringAcceptsEmptyWhenAllowed(): void
    {
        $result = Validator::string('', allowEmpty: true);
        $this->assertSame('', $result);
    }

    public function testStringHandlesUtf8Correctly(): void
    {
        $result = Validator::string('Über Äpfel', minLength: 5, maxLength: 20);
        $this->assertSame('Über Äpfel', $result);
    }

    // ========== Slug-Validierung ==========

    public function testSlugAcceptsValidSlug(): void
    {
        $result = Validator::slug('my-page-123');
        $this->assertSame('my-page-123', $result);
    }

    public function testSlugRejectsUppercase(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('lowercase letters, numbers, and hyphens');
        Validator::slug('My-Page');
    }

    public function testSlugRejectsUmlauts(): void
    {
        $this->expectException(ValidationException::class);
        Validator::slug('über-uns');
    }

    public function testSlugRejectsSlashes(): void
    {
        $this->expectException(ValidationException::class);
        Validator::slug('path/to/page');
    }

    public function testSlugRejectsPathTraversal(): void
    {
        $this->expectException(ValidationException::class);
        Validator::slug('../etc/passwd');
    }

    public function testSlugRejectsConsecutiveHyphens(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('consecutive hyphens');
        Validator::slug('my--page');
    }

    public function testSlugRejectsLeadingHyphen(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot start or end');
        Validator::slug('-mypage');
    }

    public function testSlugRejectsTrailingHyphen(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot start or end');
        Validator::slug('mypage-');
    }

    public function testSlugRejectsReservedNames(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('reserved');
        Validator::slug('admin');
    }

    public function testSlugEnforcesMaxLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed');
        Validator::slug(str_repeat('a', 201));
    }

    public function testSlugRejectsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot be empty');
        Validator::slug('');
    }

    // ========== Enum-Validierung ==========

    public function testEnumAcceptsValidValue(): void
    {
        $result = Validator::enum('published', ['draft', 'published'], 'status');
        $this->assertSame('published', $result);
    }

    public function testEnumRejectsInvalidValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('status must be one of: draft, published');
        Validator::enum('invalid', ['draft', 'published'], 'status');
    }

    public function testEnumRejectsNonString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a string');
        Validator::enum(123, ['draft', 'published'], 'status');
    }

    public function testEnumUsesStrictComparison(): void
    {
        $this->expectException(ValidationException::class);
        Validator::enum('1', [1, 2, 3], 'number');
    }

    // ========== Integer-Validierung ==========

    public function testIntegerAcceptsValidInteger(): void
    {
        $result = Validator::integer(42);
        $this->assertSame(42, $result);
    }

    public function testIntegerConvertsStringToInteger(): void
    {
        $result = Validator::integer('123');
        $this->assertSame(123, $result);
    }

    public function testIntegerRejectsNonNumeric(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a number');
        Validator::integer('abc');
    }

    public function testIntegerEnforcesMinimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be at least 10');
        Validator::integer(5, min: 10);
    }

    public function testIntegerEnforcesMaximum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed 50');
        Validator::integer(100, max: 50);
    }

    public function testIntegerAcceptsNegativeValues(): void
    {
        $result = Validator::integer(-42);
        $this->assertSame(-42, $result);
    }

    // ========== Boolean-Validierung ==========

    public function testBooleanAcceptsTrue(): void
    {
        $result = Validator::boolean(true);
        $this->assertTrue($result);
    }

    public function testBooleanAcceptsFalse(): void
    {
        $result = Validator::boolean(false);
        $this->assertFalse($result);
    }

    public function testBooleanConvertsStringOne(): void
    {
        $result = Validator::boolean('1');
        $this->assertTrue($result);
    }

    public function testBooleanConvertsStringZero(): void
    {
        $result = Validator::boolean('0');
        $this->assertFalse($result);
    }

    public function testBooleanConvertsStringTrue(): void
    {
        $result = Validator::boolean('true');
        $this->assertTrue($result);
    }

    public function testBooleanConvertsStringFalse(): void
    {
        $result = Validator::boolean('false');
        $this->assertFalse($result);
    }

    public function testBooleanConvertsNumeric(): void
    {
        $this->assertTrue(Validator::boolean(1));
        $this->assertFalse(Validator::boolean(0));
    }

    public function testBooleanRejectsInvalidString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a boolean');
        Validator::boolean('invalid');
    }

    // ========== Array-Validierung ==========

    public function testArrayAcceptsValidArray(): void
    {
        $result = Validator::array([1, 2, 3]);
        $this->assertSame([1, 2, 3], $result);
    }

    public function testArrayRejectsNonArray(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be an array');
        Validator::array('not an array');
    }

    public function testArrayEnforcesMinItems(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must contain at least 3 items');
        Validator::array([1], minItems: 3);
    }

    public function testArrayEnforcesMaxItems(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed 2 items');
        Validator::array([1, 2, 3], maxItems: 2);
    }

    public function testArrayAcceptsEmptyArray(): void
    {
        $result = Validator::array([]);
        $this->assertSame([], $result);
    }

    // ========== Email-Validierung ==========

    public function testEmailAcceptsValidEmail(): void
    {
        $result = Validator::email('test@example.com');
        $this->assertSame('test@example.com', $result);
    }

    public function testEmailRejectsInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email');
        Validator::email('not-an-email');
    }

    public function testEmailRejectsNonString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a string');
        Validator::email(123);
    }

    public function testEmailTrimsWhitespace(): void
    {
        $result = Validator::email('  test@example.com  ');
        $this->assertSame('test@example.com', $result);
    }

    // ========== URL-Validierung ==========

    public function testUrlAcceptsValidUrl(): void
    {
        $result = Validator::url('https://example.com');
        $this->assertSame('https://example.com', $result);
    }

    public function testUrlAcceptsHttp(): void
    {
        $result = Validator::url('http://example.com');
        $this->assertSame('http://example.com', $result);
    }

    public function testUrlRejectsInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid URL');
        Validator::url('not-a-url');
    }

    public function testUrlEnforcesHttpsWhenRequired(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must use HTTPS');
        Validator::url('http://example.com', requireHttps: true);
    }

    public function testUrlAcceptsHttpsWhenRequired(): void
    {
        $result = Validator::url('https://example.com', requireHttps: true);
        $this->assertSame('https://example.com', $result);
    }

    // ========== JSON-Validierung ==========

    public function testJsonAcceptsValidJson(): void
    {
        $result = Validator::json('{"key": "value"}');
        $this->assertSame(['key' => 'value'], $result);
    }

    public function testJsonRejectsInvalidJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid JSON');
        Validator::json('invalid json');
    }

    public function testJsonRejectsNonString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a string');
        Validator::json(123);
    }

    public function testJsonRejectsNonArrayResult(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must decode to an array');
        Validator::json('"just a string"');
    }

    public function testJsonHandlesNestedArrays(): void
    {
        $json = '{"nested": {"key": "value"}, "array": [1, 2, 3]}';
        $result = Validator::json($json);
        $this->assertSame([
            'nested' => ['key' => 'value'],
            'array' => [1, 2, 3]
        ], $result);
    }

    // ========== Filename-Validierung ==========

    public function testFilenameAcceptsValidFilename(): void
    {
        $result = Validator::filename('document.pdf');
        $this->assertSame('document.pdf', $result);
    }

    public function testFilenameRejectsPathTraversal(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('invalid characters');
        Validator::filename('../etc/passwd');
    }

    public function testFilenameRejectsSlashes(): void
    {
        $this->expectException(ValidationException::class);
        Validator::filename('path/to/file.txt');
    }

    public function testFilenameRejectsBackslashes(): void
    {
        $this->expectException(ValidationException::class);
        Validator::filename('path\\to\\file.txt');
    }

    public function testFilenameRejectsNullByte(): void
    {
        $this->expectException(ValidationException::class);
        Validator::filename("file\0.txt");
    }

    public function testFilenameRejectsReservedWindowsNames(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('reserved name');
        Validator::filename('CON.txt');
    }

    public function testFilenameRejectsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot be empty');
        Validator::filename('');
    }

    // ========== PageData-Validierung ==========

    public function testPageDataAcceptsValidData(): void
    {
        $data = [
            'title' => 'My Page',
            'status' => 'published',
            'sections' => [],
            'nav_main' => true,
            'nav_footer' => false,
            'nav_order' => 1,
            'nav_label' => 'Home',
            'meta_description' => 'A test page',
            'meta_keywords' => 'test, page'
        ];

        $result = Validator::pageData($data);

        $this->assertSame('My Page', $result['title']);
        $this->assertSame('published', $result['status']);
        $this->assertTrue($result['nav_main']);
        $this->assertSame(1, $result['nav_order']);
    }

    public function testPageDataRejectsEmptyTitle(): void
    {
        $this->expectException(ValidationException::class);
        Validator::pageData(['title' => '']);
    }

    public function testPageDataRejectsInvalidStatus(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('status must be one of');
        Validator::pageData([
            'title' => 'Test',
            'status' => 'invalid'
        ]);
    }

    public function testPageDataUsesDefaultValues(): void
    {
        $result = Validator::pageData(['title' => 'Test']);

        $this->assertSame('draft', $result['status']);
        $this->assertIsArray($result['sections']);
        $this->assertFalse($result['nav_main']);
    }

    // ========== LoginCredentials-Validierung ==========

    public function testLoginCredentialsAcceptsValidData(): void
    {
        $result = Validator::loginCredentials([
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $this->assertSame('admin', $result['username']);
        $this->assertSame('password123', $result['password']);
    }

    public function testLoginCredentialsRejectsEmptyUsername(): void
    {
        $this->expectException(ValidationException::class);
        Validator::loginCredentials([
            'username' => '',
            'password' => 'password123'
        ]);
    }

    public function testLoginCredentialsRejectsEmptyPassword(): void
    {
        $this->expectException(ValidationException::class);
        Validator::loginCredentials([
            'username' => 'admin',
            'password' => ''
        ]);
    }

    // ========== SanitizeHtml ==========

    public function testSanitizeHtmlEscapesHtmlTags(): void
    {
        $result = Validator::sanitizeHtml('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testSanitizeHtmlEscapesQuotes(): void
    {
        $result = Validator::sanitizeHtml('"Hello"');
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testSanitizeHtmlHandlesUtf8(): void
    {
        $result = Validator::sanitizeHtml('Über <b>Test</b>');
        $this->assertStringContainsString('Über', $result);
        $this->assertStringContainsString('&lt;b&gt;', $result);
    }
}
