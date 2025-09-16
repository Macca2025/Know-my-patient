<?php
namespace App\Infrastructure\Persistence\Support;

use PDO;

class DatabaseSupportMessageRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insert a new support message into the database.
     * @param array $data
     * @return int Inserted ID
     */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO support_messages (user_id, name, email, message, subject, ip_address, user_agent, created_at) VALUES (:user_id, :name, :email, :message, :subject, :ip_address, :user_agent, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $data['user_id'] ?? null);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':message', $data['message']);
        $stmt->bindValue(':subject', $data['subject']);
        $stmt->bindValue(':ip_address', $data['ip_address'] ?? null);
        $stmt->bindValue(':user_agent', $data['user_agent'] ?? null);
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }
}
