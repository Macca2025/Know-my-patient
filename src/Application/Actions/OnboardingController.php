<?php
namespace App\Application\Actions;

use App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Respect\Validation\Validator as v;
use Psr\Log\LoggerInterface;

class OnboardingController
{
    private Twig $twig;
    private DatabaseOnboardingEnquiryRepository $onboardingRepo;
    private LoggerInterface $logger;

    public function __construct(Twig $twig, DatabaseOnboardingEnquiryRepository $onboardingRepo, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->onboardingRepo = $onboardingRepo;
        $this->logger = $logger;
    }


    // GET: Show onboarding form
    public function onboarding(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('onboarding.html.twig', [
            'form' => [],
            'errors' => [],
            'success' => false,
            'csrf' => $csrf,
            'current_route' => 'onboarding'
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    // POST: Handle onboarding form submission
    public function submitEnquiry(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $this->logger->info('Onboarding form submission received', [
            'data_keys' => array_keys($data),
            'gdpr_consent_value' => $data['gdpr_consent'] ?? 'NOT SET',
            'gdpr_consent_type' => gettype($data['gdpr_consent'] ?? null)
        ]);
        $errors = [];
        $success = false;
        
        // Company name validation
        if (empty($data['company_name']) || strlen(trim($data['company_name'])) < 2) {
            $errors['company_name'] = 'Company name is required (minimum 2 characters).';
        }
        
        // Company website validation (optional, but validate format if provided)
        if (!empty($data['company_website'])) {
            $website = trim($data['company_website']);
            // Simple URL validation - just check if it looks like a URL
            if (!filter_var($website, FILTER_VALIDATE_URL) && !preg_match('/^https?:\/\/.+/', $website)) {
                // Try adding http:// if not present
                if (!preg_match('/^[a-z]+:\/\//', $website)) {
                    $data['company_website'] = 'https://' . $website;
                }
            }
        }
        
        // Organization type validation
        if (empty($data['organization_type'])) {
            $errors['organization_type'] = 'Organization type is required.';
        }
        
        // Contact person validation
        if (empty($data['contact_person']) || strlen(trim($data['contact_person'])) < 2) {
            $errors['contact_person'] = 'Contact person is required.';
        }
        
        // Email validation
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        
        // Phone validation (optional, but validate format if provided)
        if (!empty($data['phone'])) {
            // Remove spaces, dashes, parentheses for validation
            $cleanPhone = preg_replace('/[\s\-\(\)\+]/', '', $data['phone']);
            if (!preg_match('/^[0-9]{10,15}$/', $cleanPhone)) {
                $errors['phone'] = 'Please enter a valid phone number (10-15 digits).';
            }
        }
        
        // GDPR consent validation (checkbox must be checked)
        // Checkboxes can send 'on', '1', 'true', or any truthy value
        if (empty($data['gdpr_consent'])) {
            $errors['gdpr_consent'] = 'You must agree to the Privacy Policy to continue.';
        }

        if (empty($errors)) {
            try {
                $insertedId = $this->onboardingRepo->insert($data);
                $this->logger->info('Onboarding enquiry saved successfully', ['id' => $insertedId]);
                $success = true;
                // Clear form data on success
                $data = [];
            } catch (\Throwable $e) {
                $this->logger->error('Onboarding form submission failed: ' . $e->getMessage());
                $errors['general'] = 'Could not save your enquiry. Please try again later.';
                $success = false;
            }
        } else {
            $this->logger->warning('Onboarding form validation failed', ['errors' => array_keys($errors)]);
        }

        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('onboarding.html.twig', [
            'form' => $data,
            'errors' => $errors,
            'success' => $success,
            'csrf' => $csrf,
            'current_route' => 'onboarding'
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}
