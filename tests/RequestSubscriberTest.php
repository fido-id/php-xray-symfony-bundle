<?php

declare(strict_types=1);

namespace Tests;

use Fido\PHPXray\Segment;
use Fido\PHPXrayBundle\EventSubscriber\RequestSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestSubscriberTest extends FunctionalTestCase
{
    /**
     * @test
     * @dataProvider provide_event
     */
    public function will_test_kernel_request(?string $input, ?string $expectedTraceId, ?string $expectedParentId): void
    {
        $segment = new Segment('test');
        $subscriber = new RequestSubscriber($segment);

        $dispatcher = new EventDispatcher();

        $dispatcher->addSubscriber($subscriber);

        $event = new RequestEvent(
            $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            new Request(
                server: ['LAMBDA_INVOCATION_CONTEXT' => $input]
            ),
            1
        );

        $segment->end();

        $dispatcher->dispatch($event, KernelEvents::REQUEST);

        self::assertEquals($expectedTraceId, $segment->getTraceId());

        $parentId = $segment->jsonSerialize()['parent_id'] ?? null;
        self::assertEquals($expectedParentId, $parentId);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function provide_event(): array
    {
        return [
            'full_trace_id_header' => [
                'LAMBDA_INVOCATION_CONTEXT' => '{"traceId":"Root=1-5f5b0b1c-5f5b0b1c5f5b0b1c5f5b0b1c;Parent=5f5b0b1c5f5b0b1c;Sampled=1"}',
                'expected_trace_id' => '1-5f5b0b1c-5f5b0b1c5f5b0b1c5f5b0b1c',
                'expected_parent_id' => '5f5b0b1c5f5b0b1c',
            ],
            'only_trace_id' => [
                'LAMBDA_INVOCATION_CONTEXT' => '{"traceId":"Root=1-5f5b0b1c-5f5b0b1c5f5b0b1c5f5b0b1c"}',
                'expected_trace_id' => '1-5f5b0b1c-5f5b0b1c5f5b0b1c5f5b0b1c',
                'expected_parent_id' => null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provide_missing_event
     */
    public function will_test_kernel_request_missing_trace(?string $input): void
    {
        $segment = new Segment('test');
        $subscriber = new RequestSubscriber($segment);

        $dispatcher = new EventDispatcher();

        $dispatcher->addSubscriber($subscriber);

        $event = new RequestEvent(
            $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            new Request(
                server: ['LAMBDA_INVOCATION_CONTEXT' => $input]
            ),
            1
        );

        $segment->end();

        $dispatcher->dispatch($event, KernelEvents::REQUEST);

        self::assertMatchesRegularExpression('/^1-[0-9A-Fa-f]{8}-[0-9A-Fa-f]{24}$/', $segment->getTraceId());
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function provide_missing_event(): array
    {
        return [
            'empty_header' => [
                'LAMBDA_INVOCATION_CONTEXT' => '',
            ],
            'null_header' => [
                'LAMBDA_INVOCATION_CONTEXT' => null,
            ],
        ];
    }
}
