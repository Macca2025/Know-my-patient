<?php
declare(strict_types=1);

// Configure secure session cookies
// NHS DCB0129 Compliance: Hazard H-003 (Unauthorized Access Prevention)
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || $_SERVER['SERVER_PORT'] == 443
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Load environment to check if production
$isProduction = (($_ENV['APP_ENV'] ?? 'production') === 'production');

session_set_cookie_params([
    'lifetime' => 0,                    // Session cookie (expires when browser closes)
    'path' => '/',                      // Available across entire domain
    'domain' => '',                     // Current domain only
    'secure' => $isSecure,              // HTTPS only in production (auto-detected)
    'httponly' => true,                 // Not accessible via JavaScript (XSS protection)
    'samesite' => $isProduction ? 'Strict' : 'Lax', // Strict in production, Lax in dev
]);

// Regenerate session ID on first request (session fixation protection)
session_start();
if (!isset($_SESSION['_session_started'])) {
    session_regenerate_id(true);
    $_SESSION['_session_started'] = time();
    $_SESSION['_user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

// Session timeout: Logout users after 5 minutes of inactivity
// NHS DCB0129 Compliance: Hazard H-003 (Unauthorized Access Prevention)
if (isset($_SESSION['user_id'])) {
    $sessionTimeout = 300; // 5 minutes in seconds (300 seconds)
    
    // Check if last activity timestamp exists
    if (isset($_SESSION['_last_activity'])) {
        $inactiveTime = time() - $_SESSION['_last_activity'];
        
        if ($inactiveTime > $sessionTimeout) {
            // Session has expired due to inactivity
            $userId = $_SESSION['user_id'];
            $userEmail = $_SESSION['user_email'] ?? 'unknown';
            
            // Log the timeout event
            error_log("Session timeout: User $userId ($userEmail) logged out after $inactiveTime seconds of inactivity");
            
            // Clear session data
            session_unset();
            session_destroy();
            
            // Start new session for the timeout message
            session_start();
            $_SESSION['timeout_message'] = 'Your session has expired due to inactivity. Please login again.';
            $_SESSION['timeout_redirect'] = true;
            
            // Redirect to login page
            header('Location: /login');
            exit;
        }
    }
    
    // Update last activity timestamp for authenticated users
    $_SESSION['_last_activity'] = time();
}

// Note: Session hijacking protection (IP/User-Agent validation) should be done
// at login time in AuthController, not on every request, to avoid CSRF token issues
// and false positives from legitimate IP changes (mobile networks, VPNs, etc.)

use Slim\Views\TwigMiddleware;

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;


require __DIR__ . '/../vendor/autoload.php';

// Suppress PHP 8.4 deprecation warnings from PHP-DI vendor library
// These are third-party library issues that will be fixed in future PHP-DI releases
// They do not affect functionality and are safe to ignore
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

// Load environment variables from .env using vlucas/phpdotenv
if (class_exists('Dotenv\\Dotenv')) {
	(Dotenv\Dotenv::createImmutable(__DIR__ . '/../'))->safeLoad();
}

// Restore error reporting after PHP-DI is loaded (will be set by app settings)
// The ShutdownHandler will handle errors properly after bootstrap

// Instantiate PHP-DI ContainerBuilder
// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
	$containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Set up repositories
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Restore full error reporting after PHP-DI container is built
// This allows the app's error handlers to work properly
error_reporting(E_ALL);
// display_errors will be set by SettingsInterface based on environment

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$container->set('app', $app);
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Add Twig Middleware for Slim/Twig integration
$app->add(TwigMiddleware::createFromContainer($app, \Slim\Views\Twig::class));

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

/** @var SettingsInterface $settings */
$settings = $container->get(SettingsInterface::class);

$displayErrorDetails = $settings->get('displayErrorDetails');
$logError = $settings->get('logError');
$logErrorDetails = $settings->get('logErrorDetails');

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
$errorHandler->setContainer($container);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
