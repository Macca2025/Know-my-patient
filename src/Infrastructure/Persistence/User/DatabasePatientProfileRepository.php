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
        $stmt = $this->pdo->prepare('
            SELECT id, patient_uid, user_id, created_by, full_name, date_of_birth, gender, blood_type, 
                   allergies, medical_conditions, current_medications, emergency_contact_name, 
                   emergency_contact_phone, emergency_contact_relation, nhs_number, gp_surgery, 
                   mobility_issues, communication_needs, dietary_requirements, special_instructions, 
                   profile_picture, created_at, updated_at 
            FROM patient_profiles 
            WHERE patient_uid = :uid 
            LIMIT 1
        ');
        $stmt->execute(['uid' => $uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
