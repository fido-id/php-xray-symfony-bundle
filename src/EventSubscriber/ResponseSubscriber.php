<?php

declare(strict_types=1);

namespace Fido\PHPXrayBundle\EventSubscriber;

use Fido\PHPXray\Segment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Segment $segment,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['setTraceStatusByResponse', 0],
            ],
        ];
    }

    public function setTraceStatusByResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if ($response->isServerError()) {
            $this->segment->setFault(true);
        }

        if ($response->isClientError()) {
            $this->segment->setError(true);
        }
    }
}