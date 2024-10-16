<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Session;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Start output buffering to prevent session_start() from sending headers
        ob_start();
    }

    protected function tearDown(): void
    {
        // Clean up the output buffer
        ob_end_clean();
        parent::tearDown();
    }

    public function testStartSession()
    {
        $this->assertTrue(Session::start());
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartSessionWhenAlreadyStarted()
    {
        Session::start();
        $sessionId = session_id();
        //Try to start the session again and make sure we don't get a new session id
        Session::start();
        $sameSessionId = session_id();
        $this->assertTrue(Session::start());
        $this->assertEquals($sessionId, $sameSessionId);
    }

    public function testRegenerateId()
    {
        Session::start();
        $oldId = session_id();
        $this->assertTrue(Session::regenerateId());
        $this->assertNotEquals($oldId, session_id());
    }

    public function testSetAndGetSessionKey()
    {
        Session::start();
        Session::set('test_key', 'test_value');
        $this->assertEquals('test_value', Session::get('test_key'));
    }

    public function testGetNonExistentKey()
    {
        Session::start();
        $this->assertNull(Session::get('non_existent_key'));
    }

    public function testRemoveKey()
    {
        Session::start();
        Session::set('test_key', 'test_value');
        Session::remove('test_key');
        $this->assertNull(Session::get('test_key'));
    }

    public function testSetFlashMessage()
    {
        Session::start();
        Session::setFlashMessage('success', 'Operation successful');
        $flashMessage = Session::get('flash_message');
        $this->assertEquals(['type' => 'success', 'message' => 'Operation successful'], $flashMessage);
    }

    public function testDestroySession()
    {
        Session::start();
        Session::set('test_key', 'test_value');
        Session::destroy();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    public function testIsLoggedIn()
    {
        Session::start();
        $this->assertFalse(Session::isLoggedIn());
        Session::set('user_id', 1);
        $this->assertTrue(Session::isLoggedIn());
    }

    public function testIsAdmin()
    {
        Session::start();
        $this->assertFalse(Session::isAdmin());
        Session::set('is_admin', true);
        $this->assertTrue(Session::isAdmin());
    }

    public function testGetSessionDataForViews()
    {
        Session::start();
        Session::set('user_id', 1);
        Session::set('fornamn', 'John');
        Session::set('is_admin', true);
        Session::set('csrf_token', 'token123');
        Session::setFlashMessage('info', 'Test message');

        $expectedData = [
            'isLoggedIn' => true,
            'isAdmin' => true,
            'fornamn' => 'John',
            'user_id' => 1,
            'flash_message' => ['type' => 'info', 'message' => 'Test message'],
            'csrf_token' => 'token123'
        ];

        $this->assertEquals($expectedData, Session::getSessionDataForViews());
    }
}
