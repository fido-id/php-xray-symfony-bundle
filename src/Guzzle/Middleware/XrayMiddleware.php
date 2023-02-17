<?php

namespace Fido\PHPXrayBundle\Guzzle\Middleware;

use Fido\PHPXray\HttpSegment;
use Fido\PHPXray\Segment;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class XrayMiddleware
{
    public function __construct(
        protected Segment $segment
    )
    {
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $httpSegment = new HttpSegment(
                name: $request->getUri()->getHost(),
                url: $request->getUri()->__toString(),
                method: $request->getMethod(),
            );

            $this->segment->addSubsegment($httpSegment);

            if (array_key_exists('parent_segment', $options)) {
                $parent = $options['parent_segment'];
                $httpSegment->setParentId($parent->getId());
            }

            $request = $request->withAddedHeader('X-Amzn-Trace-Id', "Root={$this->segment->getTraceId()};Parent={$httpSegment->getId()};Sampled=1");

            $response = $handler($request, $options);

            $response->then(function (ResponseInterface $response) use ($httpSegment) {
                $httpSegment->closeWithPsrResponse($response, withContent: false);
            });

            return $response;
        };
    }
}
