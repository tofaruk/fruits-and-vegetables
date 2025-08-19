<?php

namespace App\Tests\App\EventSubscriber;

use App\EventSubscriber\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

class ApiExceptionSubscriberTest extends TestCase
{
    private function makeEvent(\Throwable $e): ExceptionEvent
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/food', 'GET');
        return new ExceptionEvent($kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, $e);
    }

    public function testHandles400(): void
    {
        $apiExceptionSubscriber = new ApiExceptionSubscriber();
        $event = $this->makeEvent(new BadRequestHttpException('Bad'));

        $apiExceptionSubscriber->onKernelException($event);
        $response = $event->getResponse();

        self::assertNotNull($response);
        self::assertSame(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Bad', $data['errors'][0]['message']);
    }

    public function testHandles422ValidationErrors(): void
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate('', [new Assert\NotBlank()]);
        $unprocessableEntityHttpException = new UnprocessableEntityHttpException(
            'Validation failed',
            new ValidationFailedException('payload', $violations)
        );
        $apiExceptionSubscriber = new ApiExceptionSubscriber();
        $event = $this->makeEvent($unprocessableEntityHttpException);

        $apiExceptionSubscriber->onKernelException($event);
        $response = $event->getResponse();

        self::assertNotNull($response);
        self::assertSame(422, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('errors', $payload);
        self::assertNotEmpty($payload['errors']);
        self::assertArrayHasKey('field', $payload['errors'][0]);
        self::assertArrayHasKey('message', $payload['errors'][0]);
        self::assertSame('This value should not be blank.', $payload['errors'][0]['message']);
    }

    public function testOtherHttpExceptionsPassthrough(): void
    {
        $apiExceptionSubscriber = new ApiExceptionSubscriber();
        $event = $this->makeEvent(new HttpException(418, 'Http error message'));

        $apiExceptionSubscriber->onKernelException($event);
        $response = $event->getResponse();

        // subscriber should not override non-400/422 custom statuses
        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());
    }
}
