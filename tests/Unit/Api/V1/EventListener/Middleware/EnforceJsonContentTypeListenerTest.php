<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\EventListener\Middleware;

use App\Api\V1\EventListener\Middleware\EnforceJsonContentTypeListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EnforceJsonContentTypeListenerTest extends TestCase
{
    private function makeEvent(string $method, array $headers = []): RequestEvent
    {
        $request = new Request([], [], [], [], [], [], null);
        $request->setMethod($method);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testAllowsJsonPostRequest(): void
    {
        $listener = new EnforceJsonContentTypeListener();
        $event = $this->makeEvent('POST', ['Content-Type' => 'application/json']);

        $this->expectNotToPerformAssertions();
        $listener->onKernelRequest($event);
    }

    public function testThrowsExceptionForNonJsonPost(): void
    {
        $listener = new EnforceJsonContentTypeListener();
        $event = $this->makeEvent('POST', ['Content-Type' => 'text/plain']);

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $listener->onKernelRequest($event);
    }

    public function testSkipsGetRequest(): void
    {
        $listener = new EnforceJsonContentTypeListener();
        $event = $this->makeEvent('GET', ['Content-Type' => 'text/plain']);

        $this->expectNotToPerformAssertions();
        $listener->onKernelRequest($event);
    }

    public function testThrowsForPutWithoutHeader(): void
    {
        $listener = new EnforceJsonContentTypeListener();
        $event = $this->makeEvent('PUT');

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $listener->onKernelRequest($event);
    }
}
