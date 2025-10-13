<?php

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\AuditLogRepository;
use PDO;

class DatabaseAuditLogRepository implements AuditLogRepository
{
    private $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function log(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_log (activity_type, user_id, target_user_id, description, ip_address) VALUES (:activity_type, :user_id, :target_user_id, :description, :ip_address)');
        $stmt->execute([
            'activity_type' => $data['activity_type'] ?? '',
            'user_id' => $data['user_id'] ?? '',
            'target_user_id' => $data['target_user_id'] ?? null,
            'description' => $data['description'] ?? '',
            'ip_address' => $data['ip_address'] ?? null,
        ]);
    }
}
