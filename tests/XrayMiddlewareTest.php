<?php

declare(strict_types=1);

namespace Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fido\PHPXray\HttpSegment;
use Fido\PHPXray\Segment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Webmozart\Assert\Assert;

class XrayMiddlewareTest extends FunctionalTestCase
{
    use ArraySubsetAsserts;

    /** @test
     * @throws \Exception
     * @throws GuzzleException
     */
    public function will_return_proper_response(): void
    {
        /* @var HandlerStack $handlerStack */
        $handlerStack = self::getContainer()->get('fido.guzzle.handler_stack');
        $container = [];
        $history = Middleware::history($container);

        Assert::isInstanceOf($handlerStack, HandlerStack::class);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        /** @var Segment $segment */
        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            'GET',
            'https://httpbin.org/status/200',
        );
        $segment->end();

        /** @var Segment[] $subsegments */
        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertCount(1, $subsegments);
        self::assertInstanceOf(HttpSegment::class, $subsegments[0]);

        /** @var array{http: array{request: array{method: string,url: string,},response: array{status: int,}},fault: bool|null,error: bool|null} $httpSegment */
        self::assertEquals('GET', $httpSegment['http']['request']['method']);
        self::assertEquals('https://httpbin.org/status/200', $httpSegment['http']['request']['url']);
        self::assertEquals(200, $httpSegment['http']['response']['status']);

        self::assertStringMatchesFormat('Root=1-00000000-000000000000000000000001;Parent=%s;Sampled=1', $container[0]['request']->getHeader('X-Amzn-Trace-Id')[0]);
    }

    /**
     * @test
     * @dataProvider responses_dataprovider
     * @param string $method
     * @param string $url
     * @param int $status
     * @param array{http: array{request: array{method: string,url: string,},response: array{status: int,}},fault: bool|null,error: bool|null} $expected
     * @throws \Exception
     * @throws GuzzleException
     */

    public function will_return_proper_response_with_a_custom_parent_segment(string $method, string $url, int $status, array $expected): void
    {
        /* @var HandlerStack $handlerStack */
        $handlerStack = self::getContainer()->get('fido.guzzle.handler_stack');
        $container = [];
        $history = Middleware::history($container);

        Assert::isInstanceOf($handlerStack, HandlerStack::class);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack, 'http_errors' => false]);

        /** @var Segment $parentSegment */
        $parentSegment = self::getContainer()->get(Segment::class);

        /** @var Segment $segment */
        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $client->request(
            $method,
            $url,
            ['parent_segment' => $parentSegment],
        );

        $segment->end();

        /** @var Segment[] $subsegments */
        $subsegments = $segment->jsonSerialize()['subsegments'];
        /** @var array{http: array{request: array{method: string,url: string,},response: array{status: int,}},fault: bool|null,error: bool|null} $httpSegment */
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

    /**
     * @return array<string,array{method:string,url:string,status:int,expected:array{http:array{request:array{method:string,url:string,},response:array{status:int,},},fault:bool|null,error:bool|null,}}>
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
     * @param array<string,string>|null $annotations
     * @param array<string,string>|null $metadata
     * @throws \Exception
     * @throws GuzzleException
     */
    public function will_populate_segment_with_annotations_and_metadata(?array $annotations, ?array $metadata): void
    {
        /* @var HandlerStack $handlerStack */
        $handlerStack = self::getContainer()->get('fido.guzzle.handler_stack');
        $container = [];
        $history = Middleware::history($container);

        Assert::isInstanceOf($handlerStack, HandlerStack::class);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        $parentSegment = self::getContainer()->get(Segment::class);

        /** @var Segment $segment */
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

        /** @var Segment[] $subsegments */
        $subsegments = $segment->jsonSerialize()['subsegments'];
        $httpSegment = $subsegments[0]->jsonSerialize();

        /* @phpstan-ignore-next-line */
        self::assertArraySubset($annotations, $httpSegment['annotations'] ?? null);
        /* @phpstan-ignore-next-line */
        self::assertArraySubset($metadata, $httpSegment['metadata'] ?? null);
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
