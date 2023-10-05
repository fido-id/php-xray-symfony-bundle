<?php

declare(strict_types=1);

namespace Fido\PHPXrayBundle\Guzzle\Middleware;

use Fido\PHPXray\HttpSegment;
use Fido\PHPXray\Segment;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

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

            if (array_key_exists('annotations', $options)) {
                Assert::isArray($options['annotations']);
                foreach ($options['annotations'] as $key => $value) {
                    $httpSegment->addAnnotation($key, $value);
                }
            }

            if (array_key_exists('metadata', $options)) {
                Assert::isArray($options['metadata']);
                foreach ($options['metadata'] as $key => $value) {
                    $httpSegment->addMetadata($key, $value);
                }
            }

            $withContent = $options['content'] ?? false;
            $withReason = $options['reason'] ?? true;
            $withHeaders = $options['headers'] ?? true;

            $this->segment->addSubsegment($httpSegment);

            if (array_key_exists('parent_segment', $options)) {
                $parent = $options['parent_segment'];
                $httpSegment->setParentId($parent->getId());
            }

            $request = $request->withAddedHeader('X-Amzn-Trace-Id', "Root={$this->segment->getTraceId()};Parent={$httpSegment->getId()};Sampled=1");

            $response = $handler($request, $options);

            $response->then(function (ResponseInterface $response) use ($httpSegment, $withContent, $withReason, $withHeaders) {
                $httpSegment->closeWithPsrResponse($response, withContent: $withContent, withReason: $withReason, withHeaders: $withHeaders);
            });

            return $response;
        };
    }
}
