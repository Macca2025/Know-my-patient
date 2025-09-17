<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Respect\Validation\Validator as v;
use Psr\Log\LoggerInterface;

class OnboardingController
{
    private Twig $twig;
    private $onboardingRepo;
    private LoggerInterface $logger;

    public function __construct(Twig $twig, $onboardingRepo, LoggerInterface $logger)
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
        $errors = [];
        $success = false;
        $companyValidator = v::notEmpty()->length(2, 150);
        $orgTypeValidator = v::notEmpty()->length(2, 100);
        $contactValidator = v::notEmpty()->length(2, 100);
        $emailValidator = v::notEmpty()->email();
        $gdprValidator = v::notEmpty()->equals('on');

        if (!$companyValidator->validate($data['company_name'] ?? null)) {
            $errors['company_name'] = 'Company name is required (2-150 characters).';
        }
        if (!$orgTypeValidator->validate($data['organization_type'] ?? null)) {
            $errors['organization_type'] = 'Organization type is required.';
        }
        if (!$contactValidator->validate($data['contact_person'] ?? null)) {
            $errors['contact_person'] = 'Contact person is required.';
        }
        if (!$emailValidator->validate($data['email'] ?? null)) {
            $errors['email'] = 'A valid email is required.';
        }
        if (!$gdprValidator->validate($data['gdpr_consent'] ?? null)) {
            $errors['gdpr_consent'] = 'GDPR consent is required.';
        }

        if (empty($errors)) {
            try {
                $this->onboardingRepo->insert($data);
                $success = true;
            } catch (\Throwable $e) {
                $errors['general'] = 'Could not save your enquiry. Please try again later.';
                $success = false;
            }
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
