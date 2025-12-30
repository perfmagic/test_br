<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $response = new JsonResponse();

        if ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
            $response->setStatusCode($statusCode);
            $response->setData(['status' => $statusCode, 'detail' => 'Authentication Required']);
        } elseif ($exception instanceof AccessDeniedException) {
            // If there is no authenticated user, respond as 401 (standard Bearer behavior)
            // Otherwise, mask as 404 to prevent enumeration
            if (null === $this->security->getUser()) {
                $statusCode = Response::HTTP_UNAUTHORIZED;
                $response->setStatusCode($statusCode);
                $response->setData(['status' => $statusCode, 'detail' => 'Authentication Required']);
            } else {
                $statusCode = Response::HTTP_NOT_FOUND;
                $response->setStatusCode($statusCode);
                $response->setData(['status' => $statusCode, 'detail' => 'Not found']);
            }
        } elseif ($exception instanceof ValidationFailedException) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $response->setStatusCode($statusCode);
            $response->setData(['status' => $statusCode, 'detail' => $exception->getMessage()]);
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $response->setStatusCode($statusCode);
            // Normalize details to avoid leaking internal exception messages
            $detail = $statusCode === Response::HTTP_NOT_FOUND ? 'Not found' : 'Request error';
            $response->setData(['status' => $statusCode, 'detail' => $detail]);
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response->setStatusCode($statusCode);
            $response->setData(['status' => $statusCode, 'detail' => 'An unexpected error occurred.']);
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }
}
