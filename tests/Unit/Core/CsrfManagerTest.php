<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\CsrfManager;
use FCMS\Core\SessionManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests für CsrfManager
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CsrfManagerTest extends TestCase
{
    private CsrfManager $csrfManager;
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'name' => 'fcms_test_session',
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'session_lifetime' => 1800,
        ];

        $this->sessionManager = new SessionManager($config);
        $this->csrfManager = new CsrfManager($this->sessionManager);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function testGenerateTokenReturnsString(): void
    {
        $token = $this->csrfManager->generateToken();

        $this->assertIsString($token);
    }

    public function testGenerateTokenReturns64CharacterString(): void
    {
        $token = $this->csrfManager->generateToken();

        $this->assertEquals(64, strlen($token));
    }

    public function testGenerateTokenStoresInSession(): void
    {
        $token = $this->csrfManager->generateToken();

        $sessionToken = $this->sessionManager->get('_csrf_token');

        $this->assertEquals($token, $sessionToken);
    }

    public function testGenerateTokenCreatesUniqueTokens(): void
    {
        $token1 = $this->csrfManager->generateToken();
        $token2 = $this->csrfManager->generateToken();

        $this->assertNotEquals($token1, $token2);
    }

    public function testGetTokenReturnsExistingToken(): void
    {
        $generatedToken = $this->csrfManager->generateToken();

        $retrievedToken = $this->csrfManager->getToken();

        $this->assertEquals($generatedToken, $retrievedToken);
    }

    public function testGetTokenGeneratesNewTokenIfNoneExists(): void
    {
        $token = $this->csrfManager->getToken();

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testValidateTokenReturnsTrueForValidToken(): void
    {
        $token = $this->csrfManager->generateToken();

        $isValid = $this->csrfManager->validateToken($token);

        $this->assertTrue($isValid);
    }

    public function testValidateTokenReturnsFalseForInvalidToken(): void
    {
        $this->csrfManager->generateToken();

        $isValid = $this->csrfManager->validateToken('invalid_token');

        $this->assertFalse($isValid);
    }

    public function testValidateTokenReturnsFalseWhenNoTokenInSession(): void
    {
        $isValid = $this->csrfManager->validateToken('some_token');

        $this->assertFalse($isValid);
    }

    public function testValidateTokenUsesTimingSafeComparison(): void
    {
        $token = $this->csrfManager->generateToken();
        $almostCorrect = substr($token, 0, -1) . 'X';

        $isValid = $this->csrfManager->validateToken($almostCorrect);

        $this->assertFalse($isValid);
    }

    public function testGetTokenFieldReturnsHtmlInput(): void
    {
        $html = $this->csrfManager->getTokenField();

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
    }

    public function testGetTokenFieldContainsToken(): void
    {
        $token = $this->csrfManager->getToken();
        $html = $this->csrfManager->getTokenField();

        $this->assertStringContainsString($token, $html);
    }

    public function testGetTokenFieldEscapesToken(): void
    {
        // Setze manuell einen Token mit Sonderzeichen
        $this->sessionManager->set('_csrf_token', '<script>alert("xss")</script>');

        $html = $this->csrfManager->getTokenField();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testValidateRequestReturnsTrueWithValidToken(): void
    {
        $token = $this->csrfManager->generateToken();
        $data = ['csrf_token' => $token];

        $isValid = $this->csrfManager->validateRequest($data);

        $this->assertTrue($isValid);
    }

    public function testValidateRequestReturnsFalseWithInvalidToken(): void
    {
        $this->csrfManager->generateToken();
        $data = ['csrf_token' => 'wrong_token'];

        $isValid = $this->csrfManager->validateRequest($data);

        $this->assertFalse($isValid);
    }

    public function testValidateRequestReturnsFalseWhenTokenMissing(): void
    {
        $this->csrfManager->generateToken();
        $data = [];

        $isValid = $this->csrfManager->validateRequest($data);

        $this->assertFalse($isValid);
    }
}
