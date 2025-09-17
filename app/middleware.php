<?php

declare(strict_types=1);


use App\Application\Middleware\SessionMiddleware;
use App\Application\Middleware\TwigGlobalsMiddleware;
// use App\Middleware\CsrfLoggingMiddleware;

use Slim\App;
use Slim\Csrf\Guard;

return function (App $app) {
    $container = $app->getContainer();
    $twig = $container->get(\Slim\Views\Twig::class);
    $app->add(SessionMiddleware::class);


    // Register and add CSRF Logging Middleware after session is started
    $csrf = $container->get('csrf');
    $logger = $container->get(Psr\Log\LoggerInterface::class);
    $twig = $container->get(\Slim\Views\Twig::class);

    // Custom CSRF failure handler
    $csrf->setFailureHandler(function ($request, $handler) use ($logger, $twig) {
        $logger->error('CSRF failure', [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'post' => $request->getParsedBody(),
            'cookies' => $request->getCookieParams(),
            'session' => $_SESSION ?? null
        ]);
        $response = $twig->getEnvironment()->render('csrf_error.html.twig');
        $res = new \Slim\Psr7\Response();
        $res->getBody()->write($response);
        return $res->withStatus(400);
    });



    $app->add(\App\Application\Middleware\TwigGlobalsMiddleware::class);
};
