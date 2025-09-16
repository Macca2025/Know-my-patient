<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Testimonial;

use PDO;

class DatabaseTestimonialRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTestimonials(): array
    {
        $stmt = $this->pdo->prepare('SELECT testimonial, name, role FROM testimonials');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
