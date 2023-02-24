<?php

namespace Fido\PHPXrayBundle\EventSubscriber;

use Fido\PHPXray\Segment;
use Fido\PHPXray\Submission\SegmentSubmitter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TerminateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Segment          $segment,
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
        $this->segment->end();
        $this->segmentSubmitter->submitSegment($this->segment);
    }
}
