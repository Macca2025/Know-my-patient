<?php

namespace App\Infrastructure\Persistence\Support;

use PDO;
use PDOException;

class DatabaseSupportMessageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insert a new support message into the database.
     * @param array<string, mixed> $data
     * @return int Inserted ID
     * @throws PDOException
     */
    public function insert(array $data): int
    {
        try {
            $sql = "INSERT INTO support_messages (user_id, name, email, message, subject, ip_address, user_agent, created_at) VALUES (:user_id, :name, :email, :message, :subject, :ip_address, :user_agent, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $data['user_id'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':message', $data['message'], PDO::PARAM_STR);
            $stmt->bindValue(':subject', $data['subject'], PDO::PARAM_STR);
            $stmt->bindValue(':ip_address', $data['ip_address'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $data['user_agent'] ?? null, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Failed to insert support message: " . $errorInfo[2]);
            }
            
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Rethrow; callers should handle logging. Avoid ad-hoc error_log here.
            throw $e;
        }
    }
}
