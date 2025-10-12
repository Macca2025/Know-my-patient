<?php
namespace App\Domain\User;

interface PatientProfileRepository
{
    /**
     * @param string $uid
     * @return array<string, mixed>|null
     */
    public function findByUid(string $uid): ?array;
}
