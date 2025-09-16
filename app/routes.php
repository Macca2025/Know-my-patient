<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->post('/onboarding', function (Request $request, Response $response) use ($app) {
        $container = $app->getContainer();
        /** @var \Slim\Views\Twig $twig */
        $twig = $container->get('view');
        /** @var \App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository $repo */
        $repo = $container->get(\App\Infrastructure\Persistence\Onboarding\DatabaseOnboardingEnquiryRepository::class);

        $data = $request->getParsedBody();
        $errors = [];
        // Basic validation (required fields)
        $required = ['company_name', 'organization_type', 'contact_person', 'email', 'gdpr_consent'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'This field is required.';
            }
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }

        if (empty($errors)) {
            try {
                $repo->insert($data);
                $success = true;
            } catch (\Throwable $e) {
                $errors['general'] = 'Could not save your enquiry. Please try again later.';
                $success = false;
            }
        } else {
            $success = false;
        }

        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $twig->getEnvironment()->render('onboarding.html.twig', [
            'form' => $data,
            'errors' => $errors,
            'success' => $success,
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    });


    // --------------------
    // Public/Home Routes
    // --------------------
    $app->get('/', [\App\Application\Actions\HomeController::class, 'home']);
    $app->get('/home', [\App\Application\Actions\HomeController::class, 'home'])->setName('home');

    // --------------------
    // Support Route
    // --------------------
    $app->map(['GET', 'POST'], '/support', [\App\Application\Actions\SupportController::class, 'support'])->setName('support');

    // --------------------
    // Authentication Routes
    // --------------------
    $app->group('', function ($group) {
        $group->map(['GET', 'POST'], '/register', [\App\Application\Actions\AuthController::class, 'register'])->setName('register');
        $group->map(['GET', 'POST'], '/login', [\App\Application\Actions\AuthController::class, 'login'])->setName('login');
    })->add(\App\Application\Middleware\GuestOnlyMiddleware::class);

    // --------------------
    // Dashboard Route (protected)
    // --------------------
    $app->get('/dashboard', [\App\Application\Actions\DashboardController::class, 'dashboard'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('dashboard');

    // --------------------
    // Onboarding Route (GET)
    // --------------------
    $app->get('/onboarding', [\App\Application\Actions\OnboardingController::class, 'onboarding'])->setName('onboarding');

    // --------------------
    // Logout Route
    // --------------------
    $app->get('/logout', [\App\Application\Actions\AuthController::class, 'logout'])->setName('logout');

    // --------------------
    // My Profile Route (GET)
    // --------------------
    $app->get('/my-profile', [\App\Application\Actions\DashboardController::class, 'myProfile'])->setName('my_profile');

    // --------------------
    // Privacy Policy Route (GET)
    // --------------------
    $app->get('/privacy-policy', [\App\Application\Actions\HomeController::class, 'privacyPolicy'])->setName('privacy_policy');

    // --------------------
    // Delete Account Route (GET, POST)
    // --------------------
    $app->map(['GET', 'POST'], '/delete-account', [\App\Application\Actions\DashboardController::class, 'deleteAccount'])->setName('delete_account');


    // --------------------
    // Forgot Password Route
    // --------------------
    $app->get('/forgot-password', [\App\Application\Actions\AuthController::class, 'forgotPassword'])->setName('forgot_password');
    // --------------------
    // Admin Dashboard Routes (protected)
    // --------------------
    $app->group('/admin', function ($group) {
        $group->get('/users', [\App\Application\Actions\AdminController::class, 'users'])->setName('admin_users');
        $group->get('/audit-dashboard', [\App\Application\Actions\AdminController::class, 'auditDashboard'])->setName('admin_audit_dashboard');
        $group->get('/support-messages', [\App\Application\Actions\AdminController::class, 'supportMessages'])->setName('admin_support_messages');
        $group->get('/card-requests', [\App\Application\Actions\AdminController::class, 'cardRequests'])->setName('admin_card_requests');
        $group->get('/testimonials', [\App\Application\Actions\AdminController::class, 'testimonials'])->setName('admin_testimonials');
        $group->get('/onboarding-enquiries', [\App\Application\Actions\AdminController::class, 'onboardingEnquiries'])->setName('admin_onboarding_enquiries');
        $group->get('/resources', [\App\Application\Actions\AdminController::class, 'resources'])->setName('admin_resources');
    })->add(\App\Application\Middleware\AdminOnlyMiddleware::class);


    // --------------------
    // NHS User Dashboard Routes (protected)
    // --------------------
    $app->group('/nhs', function ($group) {
        // Example: NHS user dashboard
        $group->get('/dashboard', [\App\Application\Actions\DashboardController::class, 'dashboardNhsUser'])->setName('dashboard_nhs_user');
        // Add more NHS user routes here
    })->add(\App\Application\Middleware\NhsUserOnlyMiddleware::class);

    // --------------------
    // Patient Dashboard Routes (protected)
    // --------------------
    $app->group('/patient', function ($group) {
        // Example: Patient dashboard
        $group->get('/dashboard', [\App\Application\Actions\DashboardController::class, 'dashboardPatient'])->setName('dashboard_patient');
        // Add more patient routes here
    })->add(\App\Application\Middleware\PatientOnlyMiddleware::class);

    // --------------------
    // Family Member Dashboard Routes (protected)
    // --------------------
    $app->group('/family', function ($group) {
        // Example: Family member dashboard
        $group->get('/dashboard', [\App\Application\Actions\DashboardController::class, 'dashboardFamily'])->setName('dashboard_family');
        // Add more family member routes here
    })->add(\App\Application\Middleware\FamilyOnlyMiddleware::class);

};