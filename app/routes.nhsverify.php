// NHS Email Verification Routes
$app->group('', function ($group) {
    $group->get('/nhsverify', [\Application\Controllers\NHSVerifyController::class, 'showVerifyPage'])->setName('nhs_verify');
    $group->post('/nhsverify/send', [\Application\Controllers\NHSVerifyController::class, 'sendVerification'])->setName('nhs_verify_send');
    $group->get('/nhsverify/confirm', [\Application\Controllers\NHSVerifyController::class, 'confirmVerification'])->setName('nhs_verify_confirm');
});
