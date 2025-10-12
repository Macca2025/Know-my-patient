<?php
namespace App\Domain\User;

interface AuditLogRepository
{
    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function log(array $data): void;
}
