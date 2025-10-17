<?php

namespace App\Application\Actions;

use App\Application\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;

class DashboardController
{
    private Twig $twig;
    private \PDO $pdo;
    private LoggerInterface $logger;
    private SessionService $sessionService;
    private CardRequestsController $cardRequestsController;

    public function __construct(Twig $twig, \PDO $pdo, LoggerInterface $logger, SessionService $sessionService, CardRequestsController $cardRequestsController)
    {
        $this->twig = $twig;
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->sessionService = $sessionService;
        $this->cardRequestsController = $cardRequestsController;
    }

        // Display page for /display route
    public function displayPage(Request $request, Response $response): Response
    {
        $userId = $this->sessionService->get('user_id');
        $uniqueCode = '';
        $qrDataUri = '';
        if ($userId) {
            $stmt = $this->pdo->prepare('SELECT uid FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user && !empty($user['uid'])) {
                $uniqueCode = $user['uid'];
                // Generate QR code using endroid/qr-code v6.x
                $appUrl = rtrim(getenv('APP_URL') ?: ($_SERVER['APP_URL'] ?? ''), '/') . '/';
                $qrContent = $appUrl . $uniqueCode;
                $qrCode = new \Endroid\QrCode\QrCode($qrContent);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                $qrDataUri = $result->getDataUri();
            }
        }
        $body = $this->twig->getEnvironment()->render('users_pages/display.html.twig', [
            'uniqueCode' => $uniqueCode,
            'qrDataUri' => $qrDataUri
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function dashboard(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        // Log CSRF values for debugging
        $this->logger->debug("Dashboard CSRF", ['name' => ($csrf['name'] ?? 'NULL'), 'value' => ($csrf['value'] ?? 'NULL')]);

        $role = $_SESSION['user_role'] ?? null;
        $dashboardPartial = null;
        $dashboardTitle = 'User Dashboard';
        $dashboardSubtitle = 'Manage your account, privacy, and records all in one place.';

        // Fetch pending card request for patient users
        $pendingCardRequest = null;
        if ($role === 'patient') {
            $userId = $this->sessionService->get('user_id');
            if ($userId) {
                $pendingCardRequest = $this->cardRequestsController->getPendingCardRequest($userId);
            }
        }

        switch ($role) {
            case 'admin':
                $dashboardPartial = 'dashboard/dashboard_admin.html.twig';
                $dashboardTitle = 'Admin Dashboard';
                $dashboardSubtitle = 'Manage the system and user accounts.';
                break;
            case 'nhs_user':
                $dashboardPartial = 'dashboard/dashboard_nhs_user.html.twig';
                $dashboardTitle = 'NHS Staff Dashboard';
                $dashboardSubtitle = 'Access patient records and NHS tools.';
                break;
            case 'patient':
                $dashboardPartial = 'dashboard/dashboard_patient.html.twig';
                break;
            case 'family':
                $dashboardPartial = 'dashboard/dashboard_family.html.twig';
                break;
            case 'healthcare_worker':
                $dashboardPartial = 'dashboard/dashboard_healthcare_worker.html.twig';
                break;
        }
        $currentUser = [
            'first_name' => $this->sessionService->get('user_name', ''),
            'surname' => '',
            'email' => $this->sessionService->get('user_email', '')
        ];
        $body = $this->twig->getEnvironment()->render('dashboard.html.twig', [
            'csrf' => $csrf,
            'current_route' => 'dashboard',
            'currentUser' => $currentUser,
            'dashboardPartial' => $dashboardPartial,
            'dashboardTitle' => $dashboardTitle,
            'dashboardSubtitle' => $dashboardSubtitle,
            'role' => $role,
            'pendingCardRequest' => $pendingCardRequest
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function dashboardNhsUser(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('dashboard/dashboard_nhs_user.html.twig', [
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function dashboardPatient(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        // Fetch pending card request for current user
        $userId = $this->sessionService->get('user_id');
        $pendingCardRequest = null;

        if ($userId) {
            $pendingCardRequest = $this->cardRequestsController->getPendingCardRequest($userId);
        }

        $body = $this->twig->getEnvironment()->render('dashboard/dashboard_patient.html.twig', [
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
            'csrf' => $csrf,
            'pendingCardRequest' => $pendingCardRequest
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    public function dashboardFamily(Request $request, Response $response): Response
    {
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];
        $body = $this->twig->getEnvironment()->render('dashboard/dashboard_family.html.twig', [
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
            'csrf' => $csrf
        ]);
        $response->getBody()->write($body);
        return $response;
    }


    public function myProfile(Request $request, Response $response): Response
    {
        // Get CSRF tokens from request
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        // Get user ID from session
        $userId = $this->sessionService->get('user_id');
        $message = null;
        $messageType = 'info';

        $currentUser = [
            'first_name' => '',
            'surname' => '',
            'email' => '',
            'user_id' => '',
            'role' => '',
            'is_verified' => 0,
            'created_at' => null
        ];

        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Handle POST request (profile update or password change)
        if ($request->getMethod() === 'POST') {
            $this->logger->info('POST request to myProfile', ['data' => $request->getParsedBody()]);
            $data = $request->getParsedBody();

            // Check if this is a password change request
            if (isset($data['change_password'])) {
                // Handle password change
                $currentPassword = $data['current_password'] ?? '';
                $newPassword = $data['new_password'] ?? '';
                $confirmPassword = $data['confirm_password'] ?? '';

                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $message = 'All password fields are required.';
                    $messageType = 'danger';
                } elseif (strlen($newPassword) < 8) {
                    $message = 'New password must be at least 8 characters long.';
                    $messageType = 'danger';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'New passwords do not match.';
                    $messageType = 'danger';
                } else {
                    // Verify current password
                    $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($user && password_verify($currentPassword, $user['password'])) {
                        // Update password
                        try {
                            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
                            $stmt = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                            $stmt->execute([$hashedPassword, $userId]);

                            $message = 'Password changed successfully!';
                            $messageType = 'success';
                        } catch (\PDOException $e) {
                            $this->logger->error("Password change error: " . $e->getMessage(), ['exception' => $e, 'user_id' => $userId]);
                            $message = 'An error occurred while changing your password. Please try again.';
                            $messageType = 'danger';
                        }
                    } else {
                        $message = 'Current password is incorrect.';
                        $messageType = 'danger';
                    }
                }
            } elseif (isset($data['delete_account'])) {
                // Redirect to confirm-deletion page so the user must explicitly confirm
                $uri = $request->getUri();
                $scheme = $uri->getScheme();
                $host = $uri->getHost();
                $port = $uri->getPort();
                $baseUrl = $scheme . '://' . $host;
                if (($scheme === 'http' && $port && $port !== 80) || ($scheme === 'https' && $port && $port !== 443)) {
                    $baseUrl .= ':' . $port;
                }
                $location = $baseUrl . '/confirm-deletion';
                $this->logger->info('myProfile redirect to confirm-deletion', ['from' => (string)$uri, 'to' => $location]);
                return $response->withHeader('Location', $location)->withStatus(302);
            } else {
                // Handle profile update
                $firstName = trim($data['first_name'] ?? '');
                $surname = trim($data['surname'] ?? '');
                $email = trim($data['email'] ?? '');

                if (empty($firstName) || empty($surname) || empty($email)) {
                    $message = 'All fields are required.';
                    $messageType = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Please enter a valid email address.';
                    $messageType = 'danger';
                } else {
                    // Check if email is already taken by another user
                    $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
                    $stmt->execute([$email, $userId]);
                    $existingUser = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($existingUser) {
                        $message = 'This email address is already in use by another account.';
                        $messageType = 'danger';
                    } else {
                        // Update user profile
                        try {
                            $stmt = $this->pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?');
                            $stmt->execute([$firstName, $surname, $email, $userId]);

                            // Update session data
                            $this->sessionService->set('user_name', $firstName . ' ' . $surname);
                            $this->sessionService->set('user_email', $email);

                            $message = 'Profile updated successfully!';
                            $messageType = 'success';

                            // Update currentUser array with new values
                            $currentUser['first_name'] = $firstName;
                            $currentUser['surname'] = $surname;
                            $currentUser['email'] = $email;
                        } catch (\PDOException $e) {
                            $this->logger->error("Profile update error: " . $e->getMessage(), ['exception' => $e, 'user_id' => $userId]);
                            $message = 'An error occurred while updating your profile. Please try again.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }

        // Fetch current user data (userId is guaranteed to be set at this point)
        $stmt = $this->pdo->prepare('SELECT id, first_name, last_name, email, role, is_verified, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($user) {
            $currentUser['first_name'] = $user['first_name'] ?? '';
            $currentUser['surname'] = $user['last_name'] ?? '';
            $currentUser['email'] = $user['email'] ?? '';
            $currentUser['user_id'] = $user['id'] ?? '';
            $currentUser['role'] = $user['role'] ?? '';
            $currentUser['is_verified'] = $user['is_verified'] ?? 0;
            $currentUser['created_at'] = $user['created_at'] ?? null;
        }

        $body = $this->twig->getEnvironment()->render('users_pages/my_profile.html.twig', [
            'currentUser' => $currentUser,
            'message' => $message,
            'messageType' => $messageType,
            'csrf' => $csrf
        ]);
        $this->logger->info('CSRF tokens for profile', ['name' => $csrf['name'], 'value' => $csrf['value']]);
        $response->getBody()->write($body);
        return $response;
    }


    public function deleteAccount(Request $request, Response $response): Response
    {
        // Immediate deletion via this controller was removed to enforce the
        // confirm-deletion flow. All deletion requests should use the
        // `/confirm-deletion` route which renders the confirmation form and
        // performs the delete after an explicit typed confirmation. Keep a
        // safe redirect in case this route is accidentally hit.
        return $response->withHeader('Location', '/login?deleted=1')->withStatus(302);
    }

    public function addPatient(Request $request, Response $response): Response
    {
        $userId = $this->sessionService->get('user_id');
        $message = '';
        $messageType = 'info';
        $formData = [];
        $currentUser = null;

        // Fetch logged-in user's full information
        if ($userId) {
            $stmt = $this->pdo->prepare('
                SELECT id, uid, first_name, last_name, email, role, created_at 
                FROM users 
                WHERE id = ?
            ');
            $stmt->execute([$userId]);
            $currentUser = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        // Handle POST request (form submission)
        if ($request->getMethod() === 'POST') {
            return $this->handlePatientSubmission($request, $response, $currentUser);
        }

        // Handle GET request (display form)
        // Check if we're editing an existing patient (from query parameter)
        $queryParams = $request->getQueryParams();
        $patientUid = $queryParams['patient_uid'] ?? null;

        if ($patientUid && $userId) {
            // Load specific patient for editing via patient_uid parameter
            $stmt = $this->pdo->prepare('
                SELECT id, patient_uid, user_id, created_by, full_name, date_of_birth, gender, blood_type, 
                       allergies, medical_conditions, current_medications, emergency_contact_name, 
                       emergency_contact_phone, emergency_contact_relation, nhs_number, gp_surgery, 
                       mobility_issues, communication_needs, dietary_requirements, special_instructions, 
                       profile_picture, created_at, updated_at 
                FROM patient_profiles 
                WHERE patient_uid = ? AND (created_by = ? OR user_id = ?)
            ');
            $stmt->execute([$patientUid, $userId, $userId]);
            $existingData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existingData) {
                $formData = $existingData;
                $message = 'Editing patient profile. Update any fields and save changes.';
                $messageType = 'info';
            } else {
                $message = 'Patient not found or you do not have permission to edit this patient.';
                $messageType = 'danger';
            }
        } elseif ($userId) {
            // No patient_uid provided - check if user already has their own patient profile
            $stmt = $this->pdo->prepare('
                SELECT id, patient_uid, user_id, created_by, full_name, date_of_birth, gender, blood_type, 
                       allergies, medical_conditions, current_medications, emergency_contact_name, 
                       emergency_contact_phone, emergency_contact_relation, nhs_number, gp_surgery, 
                       mobility_issues, communication_needs, dietary_requirements, special_instructions, 
                       profile_picture, created_at, updated_at 
                FROM patient_profiles 
                WHERE user_id = ? 
                ORDER BY created_at DESC
                LIMIT 1
            ');
            $stmt->execute([$userId]);
            $existingProfile = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existingProfile) {
                // User already has a profile - load it for editing
                $formData = $existingProfile;
                $message = 'Your existing patient profile is loaded below. Update any fields and save changes.';
                $messageType = 'info';
            } else {
                // User doesn't have a profile yet - show blank form with name pre-filled
                $message = 'Create your patient profile by filling out the form below.';
                $messageType = 'success';
            }
        }

        // Prepare CSRF tokens
        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        // Render the add patient form
        $body = $this->twig->getEnvironment()->render('users_pages/add_patient.html.twig', [
            'user_name' => $this->sessionService->get('user_name'),
            'user_role' => $this->sessionService->get('user_role'),
            'message' => $message,
            'messageType' => $messageType,
            'csrf' => $csrf,
            'formData' => $formData,
            'patient' => $formData,  // Template expects 'patient'
            'current_user' => $currentUser  // Pass logged-in user's full data
        ]);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * @param array<string, mixed>|null $currentUser
     */
    private function handlePatientSubmission(Request $request, Response $response, ?array $currentUser = null): Response
    {
        $userId = $this->sessionService->get('user_id');
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        if (!$userId) {
            $this->sessionService->set('flash_message', 'You must be logged in to save patient data.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // If currentUser not passed, fetch it
        if (!$currentUser) {
            $stmt = $this->pdo->prepare('SELECT uid FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $currentUser = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        try {
            // Check if this is an edit or new patient
            $isEdit = !empty($data['is_edit']) && !empty($data['patient_uid']);

            if ($isEdit) {
                // Update existing patient
                $patientUid = $data['patient_uid'];

                // Use absolute URL to ensure proxies/clients preserve query string
                $uri = $request->getUri();
                $scheme = $uri->getScheme();
                $host = $uri->getHost();
                $port = $uri->getPort();
                $baseUrl = $scheme . '://' . $host;
                if (($scheme === 'http' && $port && $port !== 80) || ($scheme === 'https' && $port && $port !== 443)) {
                    $baseUrl .= ':' . $port;
                }
                $location = $baseUrl . '/login?deleted=1';

                $this->logger->info('Redirecting after account deletion', ['location' => $location]);
                return $response->withHeader('Location', $location)->withStatus(302);
                $stmt = $this->pdo->prepare('
                    SELECT id FROM patient_profiles 
                    WHERE patient_uid = ? AND (created_by = ? OR user_id = ?)
                ');
                $stmt->execute([$patientUid, $userId, $userId]);
                $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$existing) {
                    $this->sessionService->set('flash_message', 'Patient not found or you do not have permission to edit.');
                    $this->sessionService->set('flash_type', 'danger');
                    return $response->withHeader('Location', '/add-patient')->withStatus(302);
                }

                // Build UPDATE query
                $updateFields = $this->buildUpdateFields($data);

                if (!empty($updateFields)) {
                    $sql = "UPDATE patient_profiles SET " . implode(', ', array_map(fn($k) => "$k = :$k", array_keys($updateFields))) . ", updated_at = NOW(), updated_by = :updated_by WHERE patient_uid = :patient_uid";

                    $updateFields['updated_by'] = $userId;
                    $updateFields['patient_uid'] = $patientUid;

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($updateFields);
                }

                // Handle file uploads
                $this->handleFileUploads($uploadedFiles, $patientUid);

                $this->sessionService->set('flash_message', 'Patient profile updated successfully!');
                $this->sessionService->set('flash_type', 'success');
            } else {
                // Create new patient - but first check if user already has a profile
                // Check by both user_id AND by patient_uid matching user's UID
                $userUid = $currentUser['uid'] ?? null;

                if (!$userUid) {
                    $this->sessionService->set('flash_message', 'Unable to create patient profile. User UID not found.');
                    $this->sessionService->set('flash_type', 'danger');
                    return $response->withHeader('Location', '/add-patient')->withStatus(302);
                }

                $stmt = $this->pdo->prepare('
                    SELECT patient_uid FROM patient_profiles 
                    WHERE user_id = ? OR patient_uid = ?
                    LIMIT 1
                ');
                $stmt->execute([$userId, $userUid]);
                $existingProfile = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($existingProfile) {
                    // User already has a profile - redirect to edit it instead
                    $this->sessionService->set('flash_message', 'You already have a patient profile. Your existing profile has been loaded for editing.');
                    $this->sessionService->set('flash_type', 'info');
                    return $response->withHeader('Location', '/add-patient?patient_uid=' . $existingProfile['patient_uid'])->withStatus(302);
                }

                // No existing profile - create new one using user's UID from users table
                $patientUid = $userUid;

                // Build INSERT query
                $insertFields = $this->buildUpdateFields($data);
                $insertFields['patient_uid'] = $patientUid;
                $insertFields['user_id'] = $userId;
                $insertFields['created_by'] = $userId;
                $insertFields['updated_by'] = $userId;

                $columns = array_keys($insertFields);
                $placeholders = array_map(fn($k) => ":$k", $columns);

                $sql = "INSERT INTO patient_profiles (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($insertFields);

                // Handle file uploads
                $this->handleFileUploads($uploadedFiles, $patientUid);

                $this->sessionService->set('flash_message', 'Patient profile created successfully!');
                $this->sessionService->set('flash_type', 'success');
            }

            // Redirect to patient list or profile
            return $response->withHeader('Location', '/add-patient?patient_uid=' . $patientUid)->withStatus(302);
        } catch (\Exception $e) {
            $this->logger->error("Error saving patient: " . $e->getMessage(), ['exception' => $e]);
            $this->sessionService->set('flash_message', 'Error saving patient profile: ' . $e->getMessage());
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/add-patient')->withStatus(302);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildUpdateFields(array $data): array
    {
        $allowedFields = [
            'patient_name', 'date_of_birth', 'gender', 'nhs_number',
            'phone_number', 'address', 'postcode', 'occupation', 'workplace',
            'allergies', 'medical_conditions', 'medications',
            'has_dementia', 'has_learning_disability', 'previous_stroke',
            'other_cognitive_conditions', 'stroke_effects', 'communication_needs',
            'gp_name', 'gp_practice', 'gp_phone',
            'emergency_contact_1_name', 'emergency_contact_1_phone', 'emergency_contact_1_relationship',
            'lpa_health_attorney_name', 'lpa_health_attorney_phone',
            'lpa_finance_attorney_name', 'lpa_finance_attorney_phone',
            'lpa_additional_notes',
            'has_respect_form', 'resuscitation_status', 'advance_directives',
            'diet_type', 'fluid_consistency', 'special_diet_notes',
            'food_preferences', 'food_dislikes',
            'personal_likes', 'personal_dislikes', 'important_memories',
            'religion', 'cultural_needs',
            'funeral_arrangements', 'organ_donation', 'funeral_details_notes',
            'additional_notes'
        ];

        $fields = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = $data[$field] ?: null;
            }
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     */
    private function handleFileUploads(array $uploadedFiles, string $patientUid): void
    {
        // Create user-specific folder: uploads/patient_documents/USER123ABC/
        $baseUploadDir = __DIR__ . '/../../../uploads/patient_documents';
        $userUploadDir = $baseUploadDir . '/' . $patientUid;

        // Create base directory if it doesn't exist
        if (!is_dir($baseUploadDir)) {
            mkdir($baseUploadDir, 0755, true);
        }

        // Create user-specific directory if it doesn't exist
        if (!is_dir($userUploadDir)) {
            mkdir($userUploadDir, 0755, true);
        }

        $fileFields = [
            'lpa_health_document' => ['name' => 'lpa_health_document_name', 'path' => 'lpa_health_document_path'],
            'lpa_finance_document' => ['name' => 'lpa_finance_document_name', 'path' => 'lpa_finance_document_path'],
            'respect_document' => ['name' => 'respect_document_name', 'path' => 'respect_document_path']
        ];

        foreach ($fileFields as $fileKey => $dbFields) {
            if (isset($uploadedFiles[$fileKey]) && $uploadedFiles[$fileKey]->getError() === UPLOAD_ERR_OK) {
                $uploadedFile = $uploadedFiles[$fileKey];

                // Validate file size (5MB)
                if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
                    continue;
                }

                // Get file extension
                $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

                // Allowed extensions for security
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    continue;
                }

                // Create filename: UID_document-type.ext (e.g., USER123ABC_lpa_health.pdf)
                $documentType = str_replace('_document', '', $fileKey); // lpa_health, lpa_finance, respect
                $newFilename = $patientUid . '_' . $documentType . '.' . $extension;

                // Full path to save file
                $fullPath = $userUploadDir . '/' . $newFilename;

                // Delete old file if exists (when updating)
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                // Move uploaded file to user's folder
                $uploadedFile->moveTo($fullPath);

                // Update database with file info
                // Store relative path for web access
                $relativePath = 'uploads/patient_documents/' . $patientUid . '/' . $newFilename;

                $stmt = $this->pdo->prepare("
                    UPDATE patient_profiles 
                    SET {$dbFields['name']} = :filename, {$dbFields['path']} = :filepath 
                    WHERE patient_uid = :patient_uid
                ");
                $stmt->execute([
                    'filename' => $uploadedFile->getClientFilename(), // Original filename
                    'filepath' => $relativePath,
                    'patient_uid' => $patientUid
                ]);
            }
        }
    }

    public function savePatientSection(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $this->sessionService->get('user_id');

        if (!$userId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        try {
            $patientProfileId = $data['patient_profile_id'] ?? null;
            $currentStep = $data['current_step'] ?? 1;

            // Generate unique patient ID if this is a new record
            if (!$patientProfileId) {
                $patientUid = 'P' . strtoupper(substr(md5(uniqid()), 0, 8));

                // Create new patient profile
                $stmt = $this->pdo->prepare('
                    INSERT INTO patient_profiles (patient_uid, created_by, created_at)
                    VALUES (?, ?, NOW())
                ');
                $stmt->execute([$patientUid, $userId]);
                $patientProfileId = $this->pdo->lastInsertId();
            }

            // Build UPDATE query dynamically based on submitted fields
            $updateFields = [];
            $updateValues = [];

            // Comprehensive list of all allowed fields matching the database schema
            $allowedFields = [
                // Basic Information
                'patient_name', 'date_of_birth', 'gender', 'nhs_number', 'phone_number',
                'occupation',

                // Address Information
                'address', 'postcode',

                // GP Information
                'gp_practice', 'gp_name', 'gp_phone',

                // Medical Information
                'blood_type', 'organ_donation', 'medical_conditions', 'medications', 'allergies',

                // Cognitive & Neurological
                'has_dementia', 'has_learning_disability', 'previous_stroke',
                'other_cognitive_conditions',

                // Communication & Cultural
                'communication_needs', 'cultural_needs',

                // Dietary Requirements
                'diet_type', 'fluid_consistency', 'food_preferences', 'food_dislikes',

                // Personal Preferences
                'personal_likes', 'personal_dislikes', 'important_memories',

                // Emergency Contact
                'emergency_contact_1_name', 'emergency_contact_1_relationship',
                'emergency_contact_1_phone',

                // Advance Care Planning
                'has_respect_form', 'advance_directives',

                // Lasting Power of Attorney
                'lpa_health_attorney_name', 'lpa_health_attorney_phone', 'lpa_health_document_name',
                'lpa_finance_attorney_name', 'lpa_finance_attorney_phone', 'lpa_finance_document_name',
                'lpa_additional_notes',

                // End of Life Planning
                'funeral_arrangements', 'funeral_details_notes',

                // Additional Notes
                'additional_notes'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Sanitize input
                    $value = trim($data[$field]);

                    // Only add non-empty values (allow empty strings for clearing fields)
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $value !== '' ? $value : null;
                }
            }

            // Validate required fields if this is step 1
            if ($currentStep == 1) {
                if (empty($data['patient_name']) || empty($data['date_of_birth'])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Patient name and date of birth are required fields'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json');
                }

                // Validate date of birth is not in the future
                $dob = new \DateTime($data['date_of_birth']);
                $today = new \DateTime();
                if ($dob > $today) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Date of birth cannot be in the future'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json');
                }
            }

            if (!empty($updateFields)) {
                $updateValues[] = $patientProfileId;
                $sql = 'UPDATE patient_profiles SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($updateValues);

                // Log the action
                $this->logAction($userId, 'patient_profile_updated', [
                    'patient_profile_id' => $patientProfileId,
                    'step' => $currentStep,
                    'fields_updated' => count($updateFields)
                ]);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Section saved successfully',
                'patientProfileId' => $patientProfileId,
                'step' => $currentStep
            ]));
        } catch (\Exception $e) {
            $this->logger->error('Error saving patient section: ' . $e->getMessage(), ['exception' => $e]);
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error saving data: ' . $e->getMessage()
            ]));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Log user actions for audit trail
     *
     * @param array<string, mixed> $details
     */
    private function logAction(string|int $userId, string $action, array $details = []): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO audit_log (user_id, activity_type, description)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$userId, $action, json_encode($details)]);
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            $this->logger->error('Failed to log action: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
