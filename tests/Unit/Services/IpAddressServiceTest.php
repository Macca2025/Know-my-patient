<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\Services\IpAddressService;
use PHPUnit\Framework\TestCase;

class IpAddressServiceTest extends TestCase
{
    public function testGetClientIpFromRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        $ip = IpAddressService::getClientIp();
        
        $this->assertEquals('192.168.1.100', $ip);
    }

    public function testGetClientIpFromCloudflare(): void
    {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.45';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        $ip = IpAddressService::getClientIp();
        
        $this->assertEquals('203.0.113.45', $ip);
    }

    public function testGetClientIpLocalhostInDevelopment(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['APP_ENV'] = 'development';
        
        $ip = IpAddressService::getClientIp();
        
        $this->assertEquals('localhost', $ip);
    }

    public function testGetClientIpReturnsUnknownWhenNoServerVars(): void
    {
        $ip = IpAddressService::getClientIp();
        
        $this->assertEquals('unknown', $ip);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
        unset($_SERVER['APP_ENV']);
    }
}
