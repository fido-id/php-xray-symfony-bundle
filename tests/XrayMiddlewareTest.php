<?php

declare(strict_types=1);

namespace Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fido\PHPXray\HttpSegment;
use Fido\PHPXray\Segment;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;

class XrayMiddlewareTest extends FunctionalTestCase
{
    use ArraySubsetAsserts;

    /** @test */
    public function will_return_proper_response(): void
    {
        $handlerStack = self::getContainer()->get('fido.guzzle.handler_stack');
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            'GET',
            'https://httpbin.org/status/200',
            [],
        );
        $segment->end();

        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertCount(1, $subsegments);
        self::assertInstanceOf(HttpSegment::class, $subsegments[0]);

        self::assertEquals('GET', $httpSegment['http']['request']['method']);
        self::assertEquals('https://httpbin.org/status/200', $httpSegment['http']['request']['url']);
        self::assertEquals(200, $httpSegment['http']['response']['status']);

        self::assertStringMatchesFormat('Root=1-00000000-000000000000000000000001;Parent=%s;Sampled=1', $container[0]['request']->getHeader('X-Amzn-Trace-Id')[0]);
    }

    /**
     * @test
     * @dataProvider responses_dataprovider
     * @phpstan-ignore-next-line "Cannot resolve argument $expected of method Tests\XrayMiddlewareTest::will_return_proper_response_with_a_custom_parent_segment()."
     */
    public function will_return_proper_response_with_a_custom_parent_segment(string $method, string $url, int $status, array $expected): void
    {
        $handlerStack = self::getContainer()->get('fido.guzzle.handler_stack');
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack, "http_errors" => false]);

        $parentSegment = self::getContainer()->get(Segment::class);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            $method,
            $url,
            ['parent_segment' => $parentSegment],
        );

        $segment->end();

        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertCount(1, $subsegments);
        self::assertInstanceOf(HttpSegment::class, $subsegments[0]);

        self::assertEquals($expected['http']['request']['method'], $httpSegment['http']['request']['method']);
        self::assertEquals($expected['http']['request']['url'], $httpSegment['http']['request']['url']);
        self::assertEquals($expected['http']['response']['status'], $httpSegment['http']['response']['status']);
        self::assertEquals($expected['fault'], $httpSegment['fault'] ?? null);
        self::assertEquals($expected['error'], $httpSegment['error'] ?? null);

        self::assertStringMatchesFormat('Root=1-00000000-000000000000000000000001;Parent=%s;Sampled=1', $container[0]['request']->getHeader('X-Amzn-Trace-Id')[0]);
    }

    /* @phpstan-ignore-next-line "Cannot resolve argument $expected of method Tests\XrayMiddlewareTest::will_return_proper_response_with_a_custom_parent_segment()."
     */
    public function responses_dataprovider(): array
    {
        return [
            '2xx_response' => [
                'method' => 'GET',
                'url' => 'https://httpbin.org/status/200',
                'status' => 200,
                'expected' => [
                    'http' => [
                        'request' => [
                            'method' => 'GET',
                            'url' => 'https://httpbin.org/status/200',
                        ],
                        'response' => [
                            'status' => 200,
                        ],
                    ],
                    'fault' => null,
                    'error' => null,
                ],
            ],
            '4xx_response' => [
                'method' => 'GET',
                'url' => 'https://httpbin.org/status/404',
                'status' => 404,
                'expected' => [
                    'http' => [
                        'request' => [
                            'method' => 'GET',
                            'url' => 'https://httpbin.org/status/404',
                        ],
                        'response' => [
                            'status' => 404,
                        ],
                    ],
                    'fault' => null,
                    'error' => true,
                ],
            ],
            '5xx_response' => [
                'method' => 'GET',
                'url' => 'https://httpbin.org/status/502',
                'status' => 502,
                'expected' => [
                    'http' => [
                        'request' => [
                            'method' => 'GET',
                            'url' => 'https://httpbin.org/status/502',
                        ],
                        'response' => [
                            'status' => 502,
                        ],
                    ],
                    'fault' => true,
                    'error' => null,
                ],
            ]
        ];
    }

    /**
     * @test
     * @dataProvider annotations_metadata_dataprovider
     * @param array<string, array<string, array<string, string>|null>> $annotations
     * @param array<string, array<string, array<string, string>|null>> $metadata
     */
    public function will_populate_segment_with_annotations_and_metadata(?array $annotations, ?array $metadata): void
    {
        $handlerStack = self::getContainer()->get('fido.guzzle.handler_stack');
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $parentSegment = self::getContainer()->get(Segment::class);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            'GET',
            'https://httpbin.org/status/200',
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
