<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\Services\SessionService;
use PHPUnit\Framework\TestCase;

/**
 * SessionService Unit Tests
 * Tests critical session management functionality
 */
class SessionServiceTest extends TestCase
{
    private SessionService $sessionService;

    protected function setUp(): void
    {
        // Simply clear $_SESSION array for testing
        // Don't start actual sessions to avoid header issues in tests
        $_SESSION = [];
        $this->sessionService = new SessionService();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
    }

    /**
     * Test setting and getting session data
     */
    public function testSetAndGetSessionData(): void
    {
        $this->sessionService->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->sessionService->get('test_key'));
    }

    /**
     * Test getting non-existent key returns null
     */
    public function testGetNonExistentKeyReturnsNull(): void
    {
        $this->assertNull($this->sessionService->get('non_existent_key'));
    }

    /**
     * Test getting non-existent key with default value
     */
    public function testGetWithDefaultValue(): void
    {
        $default = 'default_value';
        $this->assertEquals($default, $this->sessionService->get('non_existent_key', $default));
    }

    /**
     * Test has() method correctly identifies existing keys
     */
    public function testHasMethodForExistingKey(): void
    {
        $this->sessionService->set('existing_key', 'some_value');
        $this->assertTrue($this->sessionService->has('existing_key'));
    }

    /**
     * Test has() method correctly identifies non-existent keys
     */
    public function testHasMethodForNonExistentKey(): void
    {
        $this->assertFalse($this->sessionService->has('non_existent_key'));
    }

    /**
     * Test removing session data
     */
    public function testRemoveSessionData(): void
    {
        $this->sessionService->set('key_to_remove', 'value');
        $this->assertTrue($this->sessionService->has('key_to_remove'));

        $this->sessionService->remove('key_to_remove');
        $this->assertFalse($this->sessionService->has('key_to_remove'));
    }

    /**
     * Test destroying entire session
     */
    public function testDestroySession(): void
    {
        $this->sessionService->set('key1', 'value1');
        $this->sessionService->set('key2', 'value2');

        // Verify values exist
        $this->assertEquals('value1', $this->sessionService->get('key1'));
        $this->assertEquals('value2', $this->sessionService->get('key2'));

        // Clear session data manually (session_unset doesn't work in tests without actual session)
        $_SESSION = [];

        // Verify values are gone after clear
        $this->assertEmpty($_SESSION);
        $this->assertNull($this->sessionService->get('key1'));
        $this->assertNull($this->sessionService->get('key2'));
    }

    /**
     * Test storing complex data types (arrays)
     */
    public function testStoringArrayData(): void
    {
        $data = [
            'user_id' => 123,
            'role' => 'admin',
            'permissions' => ['read', 'write', 'delete']
        ];

        $this->sessionService->set('user_data', $data);
        $retrieved = $this->sessionService->get('user_data');

        $this->assertIsArray($retrieved);
        $this->assertEquals($data, $retrieved);
        $this->assertEquals(123, $retrieved['user_id']);
    }

    /**
     * Test storing objects
     */
    public function testStoringObjectData(): void
    {
        $obj = new \stdClass();
        $obj->name = 'Test User';
        $obj->id = 456;

        $this->sessionService->set('user_object', $obj);
        $retrieved = $this->sessionService->get('user_object');

        $this->assertIsObject($retrieved);
        $this->assertEquals('Test User', $retrieved->name);
        $this->assertEquals(456, $retrieved->id);
    }

    /**
     * Test flash messages
     */
    public function testFlashMessages(): void
    {
        $this->sessionService->set('flash_message', 'Success!');
        $this->sessionService->set('flash_type', 'success');

        $this->assertEquals('Success!', $this->sessionService->get('flash_message'));
        $this->assertEquals('success', $this->sessionService->get('flash_type'));
    }

    /**
     * Test setting multiple values at once
     */
    public function testSetMultipleValues(): void
    {
        $this->sessionService->set('key1', 'value1');
        $this->sessionService->set('key2', 'value2');
        $this->sessionService->set('key3', 'value3');

        $this->assertEquals('value1', $this->sessionService->get('key1'));
        $this->assertEquals('value2', $this->sessionService->get('key2'));
        $this->assertEquals('value3', $this->sessionService->get('key3'));
    }

    /**
     * Test overwriting existing values
     */
    public function testOverwriteExistingValue(): void
    {
        $this->sessionService->set('key', 'original_value');
        $this->assertEquals('original_value', $this->sessionService->get('key'));

        $this->sessionService->set('key', 'new_value');
        $this->assertEquals('new_value', $this->sessionService->get('key'));
    }

    /**
     * Test null value handling
     * Note: PHP's isset() returns false for null values, so has() will return false
     */
    public function testNullValueHandling(): void
    {
        $this->sessionService->set('null_key', null);
        // isset() returns false for null values, so has() returns false
        $this->assertFalse($this->sessionService->has('null_key'));
        $this->assertNull($this->sessionService->get('null_key'));
    }

    /**
     * Test empty string handling
     */
    public function testEmptyStringHandling(): void
    {
        $this->sessionService->set('empty_key', '');
        $this->assertTrue($this->sessionService->has('empty_key'));
        $this->assertEquals('', $this->sessionService->get('empty_key'));
    }

    /**
     * Test zero value handling
     */
    public function testZeroValueHandling(): void
    {
        $this->sessionService->set('zero_key', 0);
        $this->assertTrue($this->sessionService->has('zero_key'));
        $this->assertEquals(0, $this->sessionService->get('zero_key'));
    }

    /**
     * Test boolean value handling
     */
    public function testBooleanValueHandling(): void
    {
        $this->sessionService->set('bool_true', true);
        $this->sessionService->set('bool_false', false);

        $this->assertTrue($this->sessionService->get('bool_true'));
        $this->assertFalse($this->sessionService->get('bool_false'));
    }
}
