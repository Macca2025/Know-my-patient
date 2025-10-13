<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Testimonial;

class InMemoryTestimonialRepository
{
    /** @var array<int, array<string, string>> */
    private array $testimonials;

    public function __construct()
    {
        $this->testimonials = [
            [
                'testimonial' => 'This platform has transformed our patient care!',
                'name' => 'Dr. Jane Smith',
                'role' => 'Consultant Physician'
            ],
            [
                'testimonial' => 'Easy to use and very secure.',
                'name' => 'Nurse John Doe',
                'role' => 'Ward Nurse'
            ]
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getTestimonials(): array
    {
        return $this->testimonials;
    }
}
