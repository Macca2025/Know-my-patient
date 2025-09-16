<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Respect\Validation\Validator as v;
use Psr\Log\LoggerInterface;

class SupportController
{
    private Twig $twig;
    private $supportRepo;
    private LoggerInterface $logger;

    public function __construct(Twig $twig, $supportRepo, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->supportRepo = $supportRepo;
        $this->logger = $logger;
    }


    public function support(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $errors = [];
        $success = false;
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        if ($request->getMethod() === 'POST') {
            $nameValidator = v::notEmpty()->length(2, 100);
            $emailValidator = v::notEmpty()->email();
            $subjectValidator = v::notEmpty()->length(2, 150);
            $messageValidator = v::notEmpty()->length(10, null);

            if (!$nameValidator->validate($data['name'] ?? null)) {
                $errors['name'] = 'Name is required (2-100 characters).';
            }
            if (!$emailValidator->validate($data['email'] ?? null)) {
                $errors['email'] = 'A valid email is required.';
            }
            if (!$subjectValidator->validate($data['subject'] ?? null)) {
                $errors['subject'] = 'Subject is required (2-150 characters).';
            }
            if (!$messageValidator->validate($data['message'] ?? null)) {
                $errors['message'] = 'Message is required (min 10 characters).';
            }

            if (empty($errors)) {
                try {
                    $insertData = [
                        'user_id' => $data['user_id'] ?? null,
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'message' => $data['message'],
                        'subject' => $data['subject'],
                        'ip_address' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
                        'user_agent' => $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
                    ];
                    $this->supportRepo->insert($insertData);
                    $success = true;
                    $data = [];
                } catch (\Throwable $e) {
                    $errors['general'] = 'There was an error submitting your message. Please try again later.';
                }
            }
            // Always get fresh CSRF tokens after POST
            $csrf = $request->getAttribute('csrf');
        }

        $body = $this->twig->getEnvironment()->render('support.html.twig', [
            'form' => $data,
            'errors' => $errors,
            'success' => $success,
            'support_route' => 'support',
            'csrf' => $csrf,
            'current_route' => 'support'
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}
