<?php
namespace App\Domain\User;

interface PatientProfileRepository
{
    /**
     * @param string $uid
     * @return array|null
     */
    public function findByUid(string $uid): ?array;
}
