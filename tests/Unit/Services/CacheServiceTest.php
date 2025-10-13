<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\Services\CacheService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CacheService
 *
 * Tests caching functionality including:
 * - Basic get/set operations
 * - TTL expiry behavior
 * - Remember pattern
 * - Cache clearing
 */
class CacheServiceTest extends TestCase
{
    private string $testCacheDir;
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary cache directory for testing
        $this->testCacheDir = sys_get_temp_dir() . '/cache_test_' . uniqid();
        mkdir($this->testCacheDir, 0755, true);

        $this->cacheService = new CacheService($this->testCacheDir, 3600);
    }

    protected function tearDown(): void
    {
        // Clean up test cache directory
        if (is_dir($this->testCacheDir)) {
            $files = glob($this->testCacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testCacheDir);
        }

        parent::tearDown();
    }

    public function testSetAndGetValue(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        $this->cacheService->set($key, $value, 60);
        $result = $this->cacheService->get($key);

        $this->assertEquals($value, $result);
    }

    public function testGetReturnsNullForNonExistentKey(): void
    {
        $result = $this->cacheService->get('nonexistent_key');

        $this->assertNull($result);
    }

    public function testSetAndGetArrayValue(): void
    {
        $key = 'test_array';
        $value = ['name' => 'John', 'age' => 30, 'role' => 'admin'];

        $this->cacheService->set($key, $value, 60);
        $result = $this->cacheService->get($key);

        $this->assertEquals($value, $result);
        $this->assertIsArray($result);
    }

    public function testSetAndGetObjectValue(): void
    {
        $key = 'test_object';
        $value = (object) ['name' => 'John', 'email' => 'john@example.com'];

        $this->cacheService->set($key, $value, 60);
        $result = $this->cacheService->get($key);

        $this->assertEquals($value, $result);
        $this->assertIsObject($result);
    }

    public function testExpiredCacheReturnsNull(): void
    {
        $key = 'expired_key';
        $value = 'expired_value';

        // Set cache with 1 second TTL
        $this->cacheService->set($key, $value, 1);

        // Wait for cache to expire
        sleep(2);

        $result = $this->cacheService->get($key);

        $this->assertNull($result);
    }

    public function testRememberReturnsExistingValue(): void
    {
        $key = 'remember_key';
        $expectedValue = 'cached_value';

        // Pre-populate cache
        $this->cacheService->set($key, $expectedValue, 60);

        // Use remember - should return cached value without calling callback
        $callbackExecuted = false;
        $result = $this->cacheService->remember($key, function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'callback_value';
        }, 60);

        $this->assertEquals($expectedValue, $result);
        $this->assertFalse($callbackExecuted, 'Callback should not be executed when cache exists');
    }

    public function testRememberExecutesCallbackWhenCacheMissing(): void
    {
        $key = 'new_remember_key';
        $expectedValue = 'callback_value';

        $callbackExecuted = false;
        $result = $this->cacheService->remember($key, function () use (&$callbackExecuted, $expectedValue) {
            $callbackExecuted = true;
            return $expectedValue;
        }, 60);

        $this->assertEquals($expectedValue, $result);
        $this->assertTrue($callbackExecuted, 'Callback should be executed when cache is missing');

        // Verify value was cached
        $cachedResult = $this->cacheService->get($key);
        $this->assertEquals($expectedValue, $cachedResult);
    }

    public function testForgetRemovesCachedValue(): void
    {
        $key = 'forget_test';
        $value = 'value_to_forget';

        // Set value
        $this->cacheService->set($key, $value, 60);

        // Verify it exists
        $this->assertEquals($value, $this->cacheService->get($key));

        // Forget it
        $this->cacheService->forget($key);

        // Verify it's gone
        $this->assertNull($this->cacheService->get($key));
    }

    public function testFlushClearsAllCache(): void
    {
        // Set multiple values
        $this->cacheService->set('key1', 'value1', 60);
        $this->cacheService->set('key2', 'value2', 60);
        $this->cacheService->set('key3', 'value3', 60);

        // Verify they exist
        $this->assertEquals('value1', $this->cacheService->get('key1'));
        $this->assertEquals('value2', $this->cacheService->get('key2'));
        $this->assertEquals('value3', $this->cacheService->get('key3'));

        // Flush all
        $this->cacheService->flush();

        // Verify all are gone
        $this->assertNull($this->cacheService->get('key1'));
        $this->assertNull($this->cacheService->get('key2'));
        $this->assertNull($this->cacheService->get('key3'));
    }

    public function testCacheWithDefaultTTL(): void
    {
        $key = 'default_ttl_key';
        $value = 'default_ttl_value';

        // Set without specifying TTL (should use default from constructor)
        $this->cacheService->set($key, $value);

        // Should be retrievable
        $result = $this->cacheService->get($key);
        $this->assertEquals($value, $result);
    }

    public function testCacheHandlesSpecialCharactersInKey(): void
    {
        $key = 'special:key/with\\chars@test';
        $value = 'special_value';

        $this->cacheService->set($key, $value, 60);
        $result = $this->cacheService->get($key);

        $this->assertEquals($value, $result);
    }

    public function testCacheHandlesNullValue(): void
    {
        $key = 'null_value_key';

        $this->cacheService->set($key, null, 60);
        $result = $this->cacheService->get($key);

        // Note: null values might be treated as cache miss depending on implementation
        // This test documents the expected behavior
        $this->assertNull($result);
    }

    public function testCacheHandlesBooleanValues(): void
    {
        $trueKey = 'boolean_true';
        $falseKey = 'boolean_false';

        $this->cacheService->set($trueKey, true, 60);
        $this->cacheService->set($falseKey, false, 60);

        $this->assertTrue($this->cacheService->get($trueKey));
        $this->assertFalse($this->cacheService->get($falseKey));
    }

    public function testCacheHandlesNumericValues(): void
    {
        $intKey = 'integer_value';
        $floatKey = 'float_value';

        $this->cacheService->set($intKey, 42, 60);
        $this->cacheService->set($floatKey, 3.14, 60);

        $this->assertSame(42, $this->cacheService->get($intKey));
        $this->assertSame(3.14, $this->cacheService->get($floatKey));
    }
}
