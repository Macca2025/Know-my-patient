<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

/**
 * Password Hashing Security Tests
 * Tests ARGON2ID password hashing implementation
 */
class PasswordHashingTest extends TestCase
{
    /**
     * Test that PASSWORD_ARGON2ID is available
     */
    public function testArgon2idIsAvailable(): void
    {
        $this->assertTrue(
            defined('PASSWORD_ARGON2ID'),
            'PASSWORD_ARGON2ID constant must be available'
        );
    }

    /**
     * Test password hashing with ARGON2ID
     */
    public function testPasswordHashingWithArgon2id(): void
    {
        $password = 'SecurePassword123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    /**
     * Test password verification
     */
    public function testPasswordVerification(): void
    {
        $password = 'MySecurePassword456!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        // Correct password should verify
        $this->assertTrue(password_verify($password, $hash));

        // Wrong password should not verify
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }

    /**
     * Test that each hash is unique (salt is random)
     */
    public function testHashesAreUnique(): void
    {
        $password = 'SamePassword789!';
        $hash1 = password_hash($password, PASSWORD_ARGON2ID);
        $hash2 = password_hash($password, PASSWORD_ARGON2ID);

        $this->assertNotEquals($hash1, $hash2, 'Hashes should be unique due to random salt');
        
        // But both should verify the same password
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }

    /**
     * Test backward compatibility with BCRYPT
     */
    public function testBackwardCompatibilityWithBcrypt(): void
    {
        $password = 'LegacyPassword123!';
        
        // Create BCRYPT hash (simulating old password)
        $bcryptHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Should still verify correctly
        $this->assertTrue(password_verify($password, $bcryptHash));
        
        // Verify BCRYPT format
        $this->assertStringStartsWith('$2y$', $bcryptHash);
    }

    /**
     * Test password_needs_rehash detects old BCRYPT hashes
     */
    public function testPasswordNeedsRehashDetectsOldAlgorithm(): void
    {
        $password = 'TestPassword456!';
        
        // Create BCRYPT hash
        $bcryptHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Should need rehash to ARGON2ID
        $this->assertTrue(
            password_needs_rehash($bcryptHash, PASSWORD_ARGON2ID),
            'BCRYPT hash should need rehashing to ARGON2ID'
        );
    }

    /**
     * Test ARGON2ID hash doesn't need rehash
     */
    public function testArgon2idHashDoesntNeedRehash(): void
    {
        $password = 'ModernPassword789!';
        $argon2idHash = password_hash($password, PASSWORD_ARGON2ID);
        
        // Should NOT need rehash
        $this->assertFalse(
            password_needs_rehash($argon2idHash, PASSWORD_ARGON2ID),
            'ARGON2ID hash should not need rehashing'
        );
    }

    /**
     * Test empty password handling
     */
    public function testEmptyPasswordHandling(): void
    {
        $emptyPassword = '';
        $hash = password_hash($emptyPassword, PASSWORD_ARGON2ID);
        
        $this->assertIsString($hash);
        $this->assertTrue(password_verify($emptyPassword, $hash));
    }

    /**
     * Test very long password handling
     */
    public function testVeryLongPasswordHandling(): void
    {
        $longPassword = str_repeat('a', 1000);
        $hash = password_hash($longPassword, PASSWORD_ARGON2ID);
        
        $this->assertIsString($hash);
        $this->assertTrue(password_verify($longPassword, $hash));
    }

    /**
     * Test special characters in password
     */
    public function testSpecialCharactersInPassword(): void
    {
        $specialPassword = '!@#$%^&*()_+-=[]{}|;:,.<>?/~`"\'\\';
        $hash = password_hash($specialPassword, PASSWORD_ARGON2ID);
        
        $this->assertTrue(password_verify($specialPassword, $hash));
    }

    /**
     * Test Unicode characters in password
     */
    public function testUnicodeCharactersInPassword(): void
    {
        $unicodePassword = 'пароль密码🔐';
        $hash = password_hash($unicodePassword, PASSWORD_ARGON2ID);
        
        $this->assertTrue(password_verify($unicodePassword, $hash));
    }

    /**
     * Test case sensitivity
     */
    public function testPasswordIsCaseSensitive(): void
    {
        $password = 'CaseSensitive123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        
        $this->assertTrue(password_verify('CaseSensitive123!', $hash));
        $this->assertFalse(password_verify('casesensitive123!', $hash));
        $this->assertFalse(password_verify('CASESENSITIVE123!', $hash));
    }

    /**
     * Test hash format structure
     */
    public function testHashFormatStructure(): void
    {
        $password = 'FormatTest123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        
        // ARGON2ID hash format: $argon2id$v=19$m=65536,t=4,p=1$salt$hash
        $parts = explode('$', $hash);
        
        $this->assertGreaterThanOrEqual(6, count($parts), 'Hash should have correct structure');
        $this->assertEquals('', $parts[0]); // First element is empty due to leading $
        $this->assertEquals('argon2id', $parts[1]);
        $this->assertStringStartsWith('v=', $parts[2]);
        $this->assertStringContainsString('m=', $parts[3]); // Memory cost
        $this->assertStringContainsString('t=', $parts[3]); // Time cost
        $this->assertStringContainsString('p=', $parts[3]); // Parallelism
    }

    /**
     * Test hash length
     */
    public function testHashLength(): void
    {
        $password = 'LengthTest123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        
        // ARGON2ID hashes are typically around 90-100 characters
        $this->assertGreaterThan(80, strlen($hash));
        $this->assertLessThan(200, strlen($hash));
    }

    /**
     * Test timing attack resistance (basic check)
     */
    public function testTimingAttackResistance(): void
    {
        $password = 'TimingTest123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        
        // Measure time for correct password
        $start1 = microtime(true);
        password_verify($password, $hash);
        $time1 = microtime(true) - $start1;
        
        // Measure time for incorrect password
        $start2 = microtime(true);
        password_verify('WrongPassword', $hash);
        $time2 = microtime(true) - $start2;
        
        // Times should be relatively similar (within 50% of each other)
        // This is a basic check - true timing attack resistance is more complex
        $ratio = $time1 > 0 ? $time2 / $time1 : 1;
        $this->assertGreaterThan(0.5, $ratio, 'Timing should be similar to resist timing attacks');
        $this->assertLessThan(2.0, $ratio, 'Timing should be similar to resist timing attacks');
    }

    /**
     * Test memory-hard property (ARGON2ID uses significant memory)
     */
    public function testMemoryHardProperty(): void
    {
        $password = 'MemoryTest123!';
        
        // ARGON2ID should complete even with memory-hard algorithm
        $startMemory = memory_get_usage();
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $endMemory = memory_get_usage();
        
        $this->assertIsString($hash);
        // Memory usage should increase (basic check)
        $this->assertGreaterThanOrEqual($startMemory, $endMemory);
    }
}
