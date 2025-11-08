<?php

declare(strict_types=1);

namespace FCMS\Tests\Unit\Core;

use FCMS\Core\SessionManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests für SessionManager
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SessionManagerTest extends TestCase
{
    private SessionManager $sessionManager;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'name' => 'fcms_test_session',
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'session_lifetime' => 1800,
        ];

        $this->sessionManager = new SessionManager($this->config);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function testStartInitializesSession(): void
    {
        $this->sessionManager->start();

        $this->assertTrue($this->sessionManager->isStarted());
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartOnlyStartsOnce(): void
    {
        $this->sessionManager->start();
        $sessionId1 = session_id();

        $this->sessionManager->start();
        $sessionId2 = session_id();

        $this->assertEquals($sessionId1, $sessionId2);
    }

    public function testSetStoresValue(): void
    {
        $this->sessionManager->set('test_key', 'test_value');

        $this->assertTrue(isset($_SESSION['test_key']));
        $this->assertEquals('test_value', $_SESSION['test_key']);
    }

    public function testSetStartsSessionAutomatically(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());

        $this->sessionManager->set('key', 'value');

        $this->assertTrue($this->sessionManager->isStarted());
    }

    public function testGetReturnsValue(): void
    {
        $this->sessionManager->set('existing_key', 'existing_value');

        $value = $this->sessionManager->get('existing_key');

        $this->assertEquals('existing_value', $value);
    }

    public function testGetReturnsDefaultWhenKeyNotExists(): void
    {
        $value = $this->sessionManager->get('nonexistent', 'default');

        $this->assertEquals('default', $value);
    }

    public function testGetReturnsNullWhenNoDefault(): void
    {
        $value = $this->sessionManager->get('nonexistent');

        $this->assertNull($value);
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $this->sessionManager->set('test', 'value');

        $this->assertTrue($this->sessionManager->has('test'));
    }

    public function testHasReturnsFalseWhenKeyNotExists(): void
    {
        $this->assertFalse($this->sessionManager->has('nonexistent'));
    }

    public function testDeleteRemovesValue(): void
    {
        $this->sessionManager->set('to_delete', 'value');

        $this->sessionManager->delete('to_delete');

        $this->assertFalse($this->sessionManager->has('to_delete'));
    }

    public function testUpdateActivitySetsTimestamp(): void
    {
        $before = time();

        $this->sessionManager->updateActivity();

        $after = time();
        $lastActivity = $this->sessionManager->get('last_activity');

        $this->assertGreaterThanOrEqual($before, $lastActivity);
        $this->assertLessThanOrEqual($after, $lastActivity);
    }

    public function testRegenerateChangesSessionId(): void
    {
        $this->sessionManager->start();
        $oldId = session_id();

        $this->sessionManager->regenerate();
        $newId = session_id();

        $this->assertNotEquals($oldId, $newId);
    }

    public function testRegenerateSetsRegenerationTimestamp(): void
    {
        $this->sessionManager->regenerate();

        $timestamp = $this->sessionManager->get('last_regeneration');

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
    }

    public function testDestroyRemovesAllSessionData(): void
    {
        $this->sessionManager->set('key1', 'value1');
        $this->sessionManager->set('key2', 'value2');

        $this->sessionManager->destroy();

        $this->assertEmpty($_SESSION);
    }

    public function testDestroyMarksSessionAsNotStarted(): void
    {
        $this->sessionManager->start();

        $this->sessionManager->destroy();

        $this->assertFalse($this->sessionManager->isStarted());
    }

    public function testIsStartedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->sessionManager->isStarted());
    }

    public function testIsStartedReturnsTrueAfterStart(): void
    {
        $this->sessionManager->start();

        $this->assertTrue($this->sessionManager->isStarted());
    }
}
