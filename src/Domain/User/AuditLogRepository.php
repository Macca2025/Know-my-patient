<?php
namespace App\Domain\User;

interface AuditLogRepository
{
    /**
     * @param array $data
     * @return void
     */
    public function log(array $data): void;
}
