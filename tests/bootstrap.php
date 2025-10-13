<?php

// Define constant to indicate we're running tests
define('PHPUNIT_RUNNING', true);

require __DIR__ . '/../vendor/autoload.php';

// Initialize session array for tests (before any output)
if (session_status() === PHP_SESSION_NONE) {
    @session_start(); // Suppress warnings in case headers already sent
}

// Ensure $_SESSION is initialized
if (!isset($_SESSION)) {
    $_SESSION = [];
}
