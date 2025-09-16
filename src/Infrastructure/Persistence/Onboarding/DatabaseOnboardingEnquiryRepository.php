<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Onboarding;

use PDO;

class DatabaseOnboardingEnquiryRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insert a new onboarding enquiry into the database.
     * @param array $data
     * @return int Inserted ID
     */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO onboarding_enquiries (
            company_name, company_website, organization_type, organization_size, contact_person, job_title, email, phone,
            current_systems, integration_timeline, specific_requirements, additional_info, gdpr_consent, marketing_consent,
            status, priority, assigned_to, estimated_value, follow_up_date, demo_scheduled, proposal_sent_date, decision_deadline,
            notes, lead_source, utm_source, utm_medium, utm_campaign, created_by
        ) VALUES (
            :company_name, :company_website, :organization_type, :organization_size, :contact_person, :job_title, :email, :phone,
            :current_systems, :integration_timeline, :specific_requirements, :additional_info, :gdpr_consent, :marketing_consent,
            :status, :priority, :assigned_to, :estimated_value, :follow_up_date, :demo_scheduled, :proposal_sent_date, :decision_deadline,
            :notes, :lead_source, :utm_source, :utm_medium, :utm_campaign, :created_by
        )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'company_name' => $data['company_name'] ?? '',
            'company_website' => $data['company_website'] ?? null,
            'organization_type' => $data['organization_type'] ?? '',
            'organization_size' => $data['organization_size'] ?? null,
            'contact_person' => $data['contact_person'] ?? '',
            'job_title' => $data['job_title'] ?? null,
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'current_systems' => $data['current_systems'] ?? null,
            'integration_timeline' => $data['integration_timeline'] ?? null,
            'specific_requirements' => $data['specific_requirements'] ?? null,
            'additional_info' => $data['additional_info'] ?? null,
            'gdpr_consent' => !empty($data['gdpr_consent']) ? 1 : 0,
            'marketing_consent' => !empty($data['marketing_consent']) ? 1 : 0,
            'status' => $data['status'] ?? 'new',
            'priority' => $data['priority'] ?? 'medium',
            'assigned_to' => $data['assigned_to'] ?? null,
            'estimated_value' => $data['estimated_value'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'demo_scheduled' => $data['demo_scheduled'] ?? null,
            'proposal_sent_date' => $data['proposal_sent_date'] ?? null,
            'decision_deadline' => $data['decision_deadline'] ?? null,
            'notes' => $data['notes'] ?? null,
            'lead_source' => $data['lead_source'] ?? 'website',
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'created_by' => $data['created_by'] ?? 'system',
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
