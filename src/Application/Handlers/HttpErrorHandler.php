<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Psr\Container\ContainerInterface;
use Throwable;
use Slim\Views\Twig;

class HttpErrorHandler extends SlimErrorHandler
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
    /**
     * @inheritdoc
     */
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = 500;
        $request = $this->request;

        // Detect if the client expects HTML
        $acceptHeader = $request->getHeaderLine('Accept');
        $wantsHtml = strpos($acceptHeader, 'text/html') !== false;

        // Map exception to template or static file
        $template = 'errors/error_500.html.twig';
        if (
            $exception instanceof HttpNotFoundException ||
            $exception instanceof HttpMethodNotAllowedException
        ) {
            $statusCode = 404;
            $template = 'errors/error_404.html.twig';
        } elseif ($exception instanceof HttpForbiddenException) {
            $statusCode = 403;
            $template = 'errors/error_403.html.twig';
        }

        if ($wantsHtml && $template) {
            /** @var Twig $twig */
            $twig = $this->container->get(Twig::class);
            $response = $this->responseFactory->createResponse($statusCode);
            try {
                $response = $twig->render($response, $template, [
                    'exception' => $exception,
                    'statusCode' => $statusCode,
                    'current_route' => 'error',
                    'session' => $_SESSION ?? [],
                ]);
                return $response->withStatus($statusCode)->withHeader('Content-Type', 'text/html');
            } catch (\Throwable $e) {
                // Fallback: plain error message
                $response->getBody()->write('An error occurred rendering the error page.');
                return $response->withHeader('Content-Type', 'text/plain');
            }
        }

        // Fallback to JSON for API clients
        $error = new ActionError(
            ActionError::SERVER_ERROR,
            'An internal error has occurred while processing your request.'
        );
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $error->setDescription($exception->getMessage());
        }
        if (
            !($exception instanceof HttpException)
            && $exception instanceof Throwable
            && $this->displayErrorDetails
        ) {
            $error->setDescription($exception->getMessage());
        }
        $payload = new ActionPayload($statusCode, null, $error);
        $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT);
        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($encodedPayload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
