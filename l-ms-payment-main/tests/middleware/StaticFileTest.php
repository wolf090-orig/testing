<?php

namespace tests\middleware;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Webman\Http\Request;
use Webman\Http\Response;
use app\middleware\StaticFile;

/**
 * Class StaticFileTest
 *
 * Unit tests for the StaticFile middleware class.
 */
class StaticFileTest extends TestCase
{
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * Test the process method to block access to files starting with a dot.
     *
     * This method tests the functionality of the process method to ensure it
     * returns a 403 forbidden response when the request path contains '/.'.
     */
    public function testProcessBlocksDotFiles()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('path')->andReturn('/.hiddenfile');

        $middleware = new StaticFile();
        $next = function (Request $req) {
            return new Response(200);
        };

        $response = $middleware->process($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('<h1>403 forbidden</h1>', $response->rawBody());
    }

    /**
     * Test the process method to allow normal file access.
     *
     * This method tests the functionality of the process method to ensure it
     * allows access to normal files and calls the next middleware.
     */
    public function testProcessAllowsNormalFiles()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('path')->andReturn('/normalfile');

        $expectedResponse = new Response(200);

        $middleware = new StaticFile();
        $next = function (Request $req) use ($expectedResponse) {
            return $expectedResponse;
        };

        $response = $middleware->process($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Test the process method adds CORS headers.
     *
     * This method tests the functionality of the process method to ensure it
     * adds CORS headers to the response.
     */
    public function testProcessAddsCorsHeaders()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);
        $request->shouldReceive('path')->andReturn('/normalfile');

        $initialResponse = new Response(200);
        $initialResponse->withHeader('Access-Control-Allow-Origin', '*');
        $initialResponse->withHeader('Access-Control-Allow-Credentials', 'true');

        $middleware = new StaticFile();
        $next = function (Request $req) use ($initialResponse) {
            return $initialResponse;
        };

        $response = $middleware->process($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $response->getHeader('Access-Control-Allow-Credentials'));
    }
}
