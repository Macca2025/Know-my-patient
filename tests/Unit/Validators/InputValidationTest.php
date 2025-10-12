<?php
declare(strict_types=1);

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use Respect\Validation\Validator as v;

/**
 * Input Validation Tests
 * Tests critical input validation rules
 */
class InputValidationTest extends TestCase
{
    /**
     * Test email validation with valid emails
     */
    public function testValidEmails(): void
    {
        $validator = v::email();
        
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user123@test-domain.com',
            'firstname.lastname@company.org',
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(
                $validator->validate($email),
                "Email '{$email}' should be valid"
            );
        }
    }

    /**
     * Test email validation with invalid emails
     */
    public function testInvalidEmails(): void
    {
        $validator = v::email();
        
        $invalidEmails = [
            'not-an-email',
            '@example.com',
            'user@',
            'user @example.com',
            'user@example',
            '',
            'user@.com',
            'user..name@example.com',
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $validator->validate($email),
                "Email '{$email}' should be invalid"
            );
        }
    }

    /**
     * Test password length validation (minimum 8 characters)
     */
    public function testPasswordLengthValidation(): void
    {
        $validator = v::length(8, null);
        
        // Valid passwords
        $this->assertTrue($validator->validate('12345678'));
        $this->assertTrue($validator->validate('Password123!'));
        $this->assertTrue($validator->validate(str_repeat('a', 100)));
        
        // Invalid passwords (too short)
        $this->assertFalse($validator->validate('1234567'));
        $this->assertFalse($validator->validate('Pass'));
        $this->assertFalse($validator->validate(''));
    }

    /**
     * Test NHS number format validation
     */
    public function testNHSNumberFormatValidation(): void
    {
        // NHS number format: 10 digits
        $validator = v::digit()->length(10, 10);
        
        // Valid NHS numbers (format only, not checking checksum)
        $this->assertTrue($validator->validate('1234567890'));
        $this->assertTrue($validator->validate('9876543210'));
        
        // Invalid NHS numbers
        $this->assertFalse($validator->validate('123456789')); // Too short
        $this->assertFalse($validator->validate('12345678901')); // Too long
        $this->assertFalse($validator->validate('12345 67890')); // Contains space
        $this->assertFalse($validator->validate('123456789a')); // Contains letter
        $this->assertFalse($validator->validate(''));
    }

    /**
     * Test phone number validation
     */
    public function testPhoneNumberValidation(): void
    {
        // UK phone number: starts with 0 or +44, 10-15 digits
        $validator = v::regex('/^(\+44|0)[0-9]{9,14}$/');
        
        // Valid UK phone numbers
        $this->assertTrue($validator->validate('07123456789'));
        $this->assertTrue($validator->validate('01234567890'));
        $this->assertTrue($validator->validate('+447123456789'));
        
        // Invalid phone numbers
        $this->assertFalse($validator->validate('123456')); // Too short
        $this->assertFalse($validator->validate('12345678901234567')); // Too long
        $this->assertFalse($validator->validate('+1234567890')); // Wrong country code
        $this->assertFalse($validator->validate('07123 456 789')); // Contains spaces
    }

    /**
     * Test postcode validation (UK format)
     */
    public function testPostcodeValidation(): void
    {
        // UK postcode format
        $validator = v::regex('/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?\s?[0-9][A-Z]{2}$/i');
        
        // Valid UK postcodes
        $this->assertTrue($validator->validate('SW1A 1AA'));
        $this->assertTrue($validator->validate('M1 1AE'));
        $this->assertTrue($validator->validate('B33 8TH'));
        $this->assertTrue($validator->validate('CR2 6XH'));
        $this->assertTrue($validator->validate('DN55 1PT'));
        
        // Invalid postcodes
        $this->assertFalse($validator->validate('INVALID'));
        $this->assertFalse($validator->validate('12345'));
        $this->assertFalse($validator->validate(''));
    }

    /**
     * Test not empty validation
     */
    public function testNotEmptyValidation(): void
    {
        $validator = v::notEmpty();
        
        // Valid (not empty)
        $this->assertTrue($validator->validate('text'));
        $this->assertTrue($validator->validate('0'));
        $this->assertTrue($validator->validate([1, 2, 3]));
        
        // Invalid (empty)
        $this->assertFalse($validator->validate(''));
        $this->assertFalse($validator->validate(null));
        $this->assertFalse($validator->validate([]));
    }

    /**
     * Test alphanumeric validation
     */
    public function testAlphanumericValidation(): void
    {
        $validator = v::alnum();
        
        // Valid
        $this->assertTrue($validator->validate('abc123'));
        $this->assertTrue($validator->validate('TestUser'));
        $this->assertTrue($validator->validate('12345'));
        
        // Invalid
        $this->assertFalse($validator->validate('user@email'));
        $this->assertFalse($validator->validate('user name'));
        $this->assertFalse($validator->validate('user-name'));
    }

    /**
     * Test date validation
     */
    public function testDateValidation(): void
    {
        $validator = v::date('Y-m-d');
        
        // Valid dates
        $this->assertTrue($validator->validate('2025-01-15'));
        $this->assertTrue($validator->validate('1990-12-31'));
        $this->assertTrue($validator->validate('2000-06-15'));
        
        // Invalid dates
        $this->assertFalse($validator->validate('2025-13-01')); // Invalid month
        $this->assertFalse($validator->validate('2025-02-30')); // Invalid day
        $this->assertFalse($validator->validate('15/01/2025')); // Wrong format
        $this->assertFalse($validator->validate('not-a-date'));
    }

    /**
     * Test integer validation
     */
    public function testIntegerValidation(): void
    {
        $validator = v::intVal();
        
        // Valid integers
        $this->assertTrue($validator->validate(123));
        $this->assertTrue($validator->validate('456'));
        $this->assertTrue($validator->validate(0));
        $this->assertTrue($validator->validate(-789));
        
        // Invalid
        $this->assertFalse($validator->validate('123.45'));
        $this->assertFalse($validator->validate('abc'));
        $this->assertFalse($validator->validate(''));
    }

    /**
     * Test URL validation
     */
    public function testUrlValidation(): void
    {
        $validator = v::url();
        
        // Valid URLs
        $this->assertTrue($validator->validate('https://example.com'));
        $this->assertTrue($validator->validate('http://www.test.co.uk'));
        $this->assertTrue($validator->validate('https://subdomain.example.org/path'));
        
        // Invalid URLs
        $this->assertFalse($validator->validate('not a url'));
        $this->assertFalse($validator->validate('example.com')); // Missing protocol
        $this->assertFalse($validator->validate(''));
    }

    /**
     * Test enum validation (role types)
     */
    public function testRoleEnumValidation(): void
    {
        $validRoles = ['patient', 'nhs_user', 'admin', 'family'];
        $validator = v::in($validRoles);
        
        // Valid roles
        foreach ($validRoles as $role) {
            $this->assertTrue($validator->validate($role));
        }
        
        // Invalid roles
        $this->assertFalse($validator->validate('superadmin'));
        $this->assertFalse($validator->validate('user'));
        $this->assertFalse($validator->validate(''));
    }

    /**
     * Test combined validation (email and not empty)
     */
    public function testCombinedValidation(): void
    {
        $validator = v::notEmpty()->email();
        
        // Valid
        $this->assertTrue($validator->validate('user@example.com'));
        
        // Invalid
        $this->assertFalse($validator->validate(''));
        $this->assertFalse($validator->validate('not-an-email'));
    }

    /**
     * Test optional validation (allows null or valid value)
     */
    public function testOptionalValidation(): void
    {
        $validator = v::optional(v::email());
        
        // Valid (null or valid email)
        $this->assertTrue($validator->validate(null));
        $this->assertTrue($validator->validate('user@example.com'));
        
        // Invalid (not null and not valid email)
        $this->assertFalse($validator->validate('not-an-email'));
    }

    /**
     * Test SQL injection prevention (basic check)
     */
    public function testSQLInjectionPatterns(): void
    {
        // These patterns should be rejected by proper validation
        $sqlInjectionPatterns = [
            "admin'--",
            "1' OR '1'='1",
            "'; DROP TABLE users--",
            "1; DELETE FROM users WHERE 1=1--",
        ];
        
        $emailValidator = v::email();
        
        foreach ($sqlInjectionPatterns as $pattern) {
            $this->assertFalse(
                $emailValidator->validate($pattern),
                "SQL injection pattern '{$pattern}' should be rejected"
            );
        }
    }

    /**
     * Test XSS prevention (basic check)
     */
    public function testXSSPatterns(): void
    {
        // These patterns should be properly escaped/rejected
        $xssPatterns = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
        ];
        
        $emailValidator = v::email();
        
        foreach ($xssPatterns as $pattern) {
            $this->assertFalse(
                $emailValidator->validate($pattern),
                "XSS pattern should be rejected by email validator"
            );
        }
    }
}
