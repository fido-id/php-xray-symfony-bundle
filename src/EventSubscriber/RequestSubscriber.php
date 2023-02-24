<?php

namespace Fido\PHPXrayBundle\EventSubscriber;

use Fido\PHPXray\Segment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

class RequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Segment $segment,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $requestContext = $event->getRequest()->server->get('LAMBDA_INVOCATION_CONTEXT');
        Assert::nullOrString($requestContext);

        $lambdaContext = $requestContext !== null ? \json_decode($requestContext, true) : null;
        Assert::nullOrIsArray($lambdaContext);

        $root = '/Root=(\d-[0-9A-Fa-f]{8}-[0-9A-Fa-f]{24})/';
        \preg_match($root, $lambdaContext['traceId'] ?? null, $rootMatches);
        $traceId = $rootMatches[1] ?? self::generateTraceId();

        $parent = '/Parent=([0-9A-Fa-f]{16})/';
        \preg_match($parent, $lambdaContext['traceId'] ?? null, $parentMatches);
        $parentId = $parentMatches[1] ?? null;

        $this->segment->setTraceId($traceId);
        $this->segment->setParentId($parentId);
    }

    protected static function generateTraceId(): string
    {
        $startHex = dechex((int)microtime(true));
        $uuid = bin2hex(random_bytes(12));
        return "1-{$startHex}-{$uuid}";
    }
}