<?php
namespace App\Infrastructure\Persistence\User;

use App\Domain\User\PatientProfileRepository;
use PDO;

class DatabasePatientProfileRepository implements PatientProfileRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUid(string $uid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM patient_profiles WHERE patient_uid = :uid LIMIT 1');
        $stmt->execute(['uid' => $uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
