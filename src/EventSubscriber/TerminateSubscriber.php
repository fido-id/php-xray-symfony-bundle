<?php

namespace Fido\PHPXrayBundle\EventSubscriber;

use Fido\PHPXray\Submission\SegmentSubmitter;
use Fido\PHPXray\Segment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Log\Logger;

class TerminateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Segment $segment,
        protected SegmentSubmitter $segmentSubmitter,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $logger = new Logger();

        $logger->debug("[TerminateEvent] TraceID: {$this->segment->getTraceId()}");

        $this->segment->end();
        $this->segmentSubmitter->submitSegment($this->segment);
    }
}
