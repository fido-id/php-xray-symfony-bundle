<?php

namespace Test;

use Aws\History;
use Fido\PHPXray\HttpSegment;
use Fido\PHPXray\Segment;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class XrayMiddlewareTest extends FunctionalTestCase
{
    /** @test */
    public function will_return_proper_response(): void
    {
        $handlerStack = self::getContainer()->get(HandlerStack::class);
        $container = [];
        $history = Middleware::history($container);

        $handlerStack->push($history);

        $client = new Client(["handler" => $handlerStack]);

        $segment = self::getContainer()->get(Segment::class);
        $segment->setTraceId('1-00000000-000000000000000000000001');
        $response = $client->request(
            "GET",
            "https://ifconfig.me/all.json",
            [],
        );
        $segment->end();

        $subsegments = $segment->jsonSerialize()["subsegments"];
        $httpSegment = $subsegments[0]->jsonSerialize();

        self::assertCount(1, $subsegments);
        self::assertInstanceOf(HttpSegment::class, $subsegments[0]);

        self::assertEquals("GET", $httpSegment["http"]["request"]["method"]);
        self::assertEquals("https://ifconfig.me/all.json", $httpSegment["http"]["request"]["url"]);
        self::assertEquals(200, $httpSegment["http"]["response"]["status"]);

        self::assertStringMatchesFormat("Root=1-00000000-000000000000000000000001;Parent=%s;Sampled=1", $container[0]['request']->getHeader('X-Amzn-Trace-Id')[0]);
    }
}
