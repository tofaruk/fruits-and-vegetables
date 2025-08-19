<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Uniform JSON error responses under /api routes.
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /** @inheritDoc */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 255]];
    }

    /** Convert framework exceptions to API JSON response. */
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $e = $event->getThrowable();

        $response = match (true) {
            $e instanceof UnprocessableEntityHttpException => $this->make422($e),
            $e instanceof BadRequestHttpException          => $this->make400($e->getMessage()),
            $e instanceof NotFoundHttpException            => $this->make404(),
            default                                        => $this->make500(),
        };

        $event->setResponse($response);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    private function make400(string $message): JsonResponse
    {
        return new JsonResponse([
            'errors' => [['message' => $message ?: 'Bad Request']],
            'status' => Response::HTTP_BAD_REQUEST,
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return JsonResponse
     */
    private function make404(): JsonResponse
    {
        return new JsonResponse([
            'error'  => 'Not found',
            'status' => Response::HTTP_NOT_FOUND,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * @param UnprocessableEntityHttpException $e
     * @return JsonResponse
     */
    private function make422(UnprocessableEntityHttpException $e): JsonResponse
    {
        $errors = [];
        $prev   = $e->getPrevious();

        if ($prev instanceof ValidationFailedException) {
            foreach ($prev->getViolations() as $v) {
                $errors[] = [
                    'field'   => $v->getPropertyPath() ?: 'payload',
                    'message' => $v->getMessage(),
                ];
            }
        } else {
            $errors[] = ['message' => $e->getMessage() ?: 'Unprocessable Entity'];
        }

        return new JsonResponse([
            'errors' => $errors,
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return JsonResponse
     */
    private function make500(): JsonResponse
    {
        return new JsonResponse([
            'error'  => 'Internal Server Error.',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
