<?php

namespace Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fido\PHPXray\HttpSegment;
use Fido\PHPXray\Segment;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class XrayMiddlewareTest extends FunctionalTestCase
{
    use ArraySubsetAsserts;

    /** @test */
    public function will_return_proper_response(): void
    {
        $handlerStack = self::getContainer()->get(HandlerStack::class);
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            'GET',
            'https://ifconfig.me/all.json',
            [],
        );
        $segment->end();

        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertCount(1, $subsegments);
        self::assertInstanceOf(HttpSegment::class, $subsegments[0]);

        self::assertEquals('GET', $httpSegment['http']['request']['method']);
        self::assertEquals('https://ifconfig.me/all.json', $httpSegment['http']['request']['url']);
        self::assertEquals(200, $httpSegment['http']['response']['status']);

        self::assertStringMatchesFormat('Root=1-00000000-000000000000000000000001;Parent=%s;Sampled=1', $container[0]['request']->getHeader('X-Amzn-Trace-Id')[0]);
    }

    /** @test */
    public function will_return_proper_response_with_a_custom_parent_segment(): void
    {
        $handlerStack = self::getContainer()->get(HandlerStack::class);
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $parentSegment = self::getContainer()->get(Segment::class);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            'GET',
            'https://ifconfig.me/all.json',
            ['parent_segment' => $parentSegment],
        );

        $segment->end();

        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertCount(1, $subsegments);
        self::assertInstanceOf(HttpSegment::class, $subsegments[0]);

        self::assertEquals('GET', $httpSegment['http']['request']['method']);
        self::assertEquals('https://ifconfig.me/all.json', $httpSegment['http']['request']['url']);
        self::assertEquals(200, $httpSegment['http']['response']['status']);

        self::assertStringMatchesFormat('Root=1-00000000-000000000000000000000001;Parent=%s;Sampled=1', $container[0]['request']->getHeader('X-Amzn-Trace-Id')[0]);
    }

    /**
     * @test
     * @dataProvider annotations_metadata_dataprovider
     * @param array<string, array<string, array<string, string>|null>> $annotations
     * @param array<string, array<string, array<string, string>|null>> $metadata
     */
    public function will_populate_segment_with_annotations_and_metadata(?array $annotations, ?array $metadata): void
    {
        $handlerStack = self::getContainer()->get(HandlerStack::class);
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $parentSegment = self::getContainer()->get(Segment::class);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            'GET',
            'https://ifconfig.me/all.json',
            [
                'parent_segment' => $parentSegment,
                'annotations' => $annotations,
                'metadata' => $metadata
            ],
        );

        $segment->end();

        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertArraySubset($annotations, $httpSegment['annotations'] ?? []);
        self::assertArraySubset($metadata, $httpSegment['metadata'] ?? []);
    }

    /**
     * @return array<string, array<string, array<string, string>|null>>
     */
    public function annotations_metadata_dataprovider(): array
    {
        return [
            'no_annotation_and_metadata' => [
                'annotations' => [],
                'metadata' => [],
            ],
            'single_annotation' => [
                'annotations' => ['foo' => 'bar'],
                'metadata' => [],
            ],
            'single_metadata' => [
                'annotations' => [],
                'metadata' => ['foo' => 'bar'],
            ],
            'single_annotation_metadata' => [
                'annotations' => ['foo' => 'bar'],
                'metadata' => ['foo' => 'bar'],
            ],
            'multiple_annotation' => [
                'annotations' => ['foo' => 'bar', 'baz' => 'qux'],
                'metadata' => [],
            ],
            'multiple_metadata' => [
                'annotations' => [],
                'metadata' => ['foo' => 'bar', 'baz' => 'qux'],
            ],
            'multiple' => [
                'annotations' => ['foo' => 'bar', 'baz' => 'qux'],
                'metadata' => ['foo' => 'bar', 'baz' => 'qux'],
            ],
        ];
    }
}
