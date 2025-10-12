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

    // --------------------
    // Health Check Endpoint (Public, for monitoring)
    // --------------------
    $app->get('/health', [\App\Application\Actions\HealthCheckAction::class, '__invoke'])
        ->setName('health_check');

    // --------------------
    // Patient Passport Lookup (NHS User)
    // --------------------
    $app->map(['GET', 'POST'], '/patient-passport', [\App\Application\Actions\Healthcare\PatientPassportAction::class, '__invoke'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('patient_passport');

    $app->post('/onboarding', [\App\Application\Actions\OnboardingController::class, 'submitEnquiry'])
        ->setName('onboarding_submit');

    // --------------------
    // Display Page Route (GET)
    // --------------------
    $app->get('/display', [\App\Application\Actions\DashboardController::class, 'displayPage'])->setName('display_page');

    // --------------------
    // Account Deletion Confirmation Route (GET & POST)
    // --------------------
    $app->map(['GET', 'POST'], '/confirm-deletion', [\App\Application\Actions\User\ConfirmDeletionAction::class, '__invoke'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('confirm_deletion');

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
        // Registration with stricter rate limiting (3 attempts per 30 minutes)
        $group->map(['GET', 'POST'], '/register', [\App\Application\Actions\AuthController::class, 'register'])
            ->add('RegistrationRateLimitMiddleware')
            ->setName('register');
        // Login with standard rate limiting (10 attempts per 5 minutes)
        $group->map(['GET', 'POST'], '/login', [\App\Application\Actions\AuthController::class, 'login'])
            ->add(\App\Application\Middleware\RateLimitMiddleware::class)
            ->setName('login');
        // Forgot Password - Show Form (GET) or Handle Submission (POST)
        $group->get('/forgot-password', [\App\Application\Actions\PasswordResetController::class, 'showForgotPasswordForm'])
            ->setName('forgot_password');
        $group->post('/forgot-password', [\App\Application\Actions\PasswordResetController::class, 'handleForgotPassword'])
            ->add(\App\Application\Middleware\RateLimitMiddleware::class)
            ->setName('forgot_password_submit');
        // Reset Password - Show Form (GET) or Handle Submission (POST)
        $group->get('/reset-password', [\App\Application\Actions\PasswordResetController::class, 'showResetPasswordForm'])
            ->setName('reset_password');
        $group->post('/reset-password', [\App\Application\Actions\PasswordResetController::class, 'handleResetPassword'])
            ->setName('reset_password_submit');
    })->add(\App\Application\Middleware\GuestOnlyMiddleware::class);

    // --------------------
    // Dashboard Route (protected)
    // --------------------
    $app->get('/dashboard', [\App\Application\Actions\DashboardController::class, 'dashboard'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('dashboard');

    // --------------------
    // Request Physical Card Route (protected, POST only)
    // --------------------
    $app->post('/dashboard/request-card', [\App\Application\Actions\CardRequestsController::class, 'requestPhysicalCard'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('request_physical_card');

    // --------------------
    // Onboarding Route (GET)
    // --------------------
    $app->get('/onboarding', [\App\Application\Actions\OnboardingController::class, 'onboarding'])->setName('onboarding');

    // --------------------
    // Logout Route
    // --------------------
    $app->get('/logout', [\App\Application\Actions\AuthController::class, 'logout'])->setName('logout');

    // --------------------
    // My Profile Route (GET, POST)
    // --------------------
    $app->map(['GET', 'POST'], '/my-profile', [\App\Application\Actions\DashboardController::class, 'myProfile'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('my_profile');

    // --------------------
    // Add Patient Route (GET, POST)
    // --------------------
    $app->map(['GET', 'POST'], '/add-patient', [\App\Application\Actions\AddPatientController::class, 'addPatient'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('add_patient');

    // --------------------
    // Save Patient Section Route (POST - AJAX)
    // --------------------
    $app->post('/add-patient/save-section', [\App\Application\Actions\AddPatientController::class, 'savePatientSection'])
        ->add(\App\Application\Middleware\AuthMiddleware::class)
        ->setName('save_patient_section');

    // --------------------
    // Privacy Policy Route (GET)
    // --------------------
    $app->get('/privacy-policy', [\App\Application\Actions\HomeController::class, 'privacyPolicy'])->setName('privacy_policy');

    // --------------------
    // Delete Account Route (GET, POST)
    // --------------------
    $app->map(['GET', 'POST'], '/delete-account', [\App\Application\Actions\DashboardController::class, 'deleteAccount'])->setName('delete_account');


    // --------------------
    // Admin Dashboard Routes (protected)
    // --------------------
    $app->group('/admin', function ($group) {
    $group->get('/users', [\App\Application\Actions\AdminController::class, 'users'])->setName('admin_users');
    $group->post('/users/delete', [\App\Application\Actions\AdminController::class, 'deleteUser'])->setName('admin_user_delete');
    $group->post('/users/suspend', [\App\Application\Actions\AdminController::class, 'suspendUser'])->setName('admin_user_suspend');
    $group->get('/audit-dashboard', [\App\Application\Actions\AdminController::class, 'auditDashboard'])->setName('admin_audit_dashboard');
    $group->get('/support-messages', [\App\Application\Actions\AdminController::class, 'supportMessages'])->setName('admin_support_messages');
    $group->post('/support-messages/status', [\App\Application\Actions\AdminController::class, 'updateSupportMessageStatus'])->setName('admin_support_message_status');
    $group->post('/support-messages/delete', [\App\Application\Actions\AdminController::class, 'deleteSupportMessage'])->setName('admin_support_message_delete');
    $group->get('/card-requests', [\App\Application\Actions\CardRequestsController::class, 'adminCardRequests'])->setName('admin_card_requests');
    $group->post('/card-requests/status', [\App\Application\Actions\CardRequestsController::class, 'updateCardRequestStatus'])->setName('admin_card_request_status');
    $group->post('/card-requests/delete', [\App\Application\Actions\CardRequestsController::class, 'deleteCardRequest'])->setName('admin_card_request_delete');
    $group->get('/testimonials', [\App\Application\Actions\AdminController::class, 'testimonials'])->setName('admin_testimonials');
    $group->post('/testimonials/delete', [\App\Application\Actions\AdminController::class, 'deleteTestimonial'])->setName('admin_testimonial_delete');
    $group->get('/onboarding-enquiries', [\App\Application\Actions\AdminController::class, 'onboardingEnquiries'])->setName('admin_onboarding_enquiries');
    $group->post('/onboarding-enquiries/assign', [\App\Application\Actions\AdminController::class, 'assignOnboardingEnquiry'])->setName('admin_onboarding_enquiry_assign');
    $group->post('/onboarding-enquiries/status', [\App\Application\Actions\AdminController::class, 'updateOnboardingEnquiryStatus'])->setName('admin_onboarding_enquiry_status');
    $group->post('/onboarding-enquiries/delete', [\App\Application\Actions\AdminController::class, 'deleteOnboardingEnquiry'])->setName('admin_onboarding_enquiry_delete');
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

    // --------------------
    // Patient Profile API (for QR/UID lookup)
    // --------------------
    $app->get('/patient/profile/{uid}', [
        \App\Application\Actions\Healthcare\PatientProfileApiAction::class,
        '__invoke'
    ]);

};