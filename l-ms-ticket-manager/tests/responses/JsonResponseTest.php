<?php

namespace tests\responses;

use PHPUnit\Framework\TestCase;
use app\responses\JsonResponse;
use support\Response;

/**
 * Class JsonResponseTest
 *
 * Unit tests for the JsonResponse class.
 */
class JsonResponseTest extends TestCase
{
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test the Success method of JsonResponse.
     *
     * This method tests the functionality of the Success method in the JsonResponse class.
     */
    public function testSuccessResponse()
    {
        $data = ['key' => 'value'];
        $response = JsonResponse::Success($data);

        $expectedResult = json_encode([
            'status' => 'success',
            'data' => $data,
            'errors' => null,
        ], JSON_UNESCAPED_UNICODE);

        // Verify the response object type
        $this->assertInstanceOf(Response::class, $response);

        // Verify the HTTP status code
        $this->assertEquals(200, $response->getStatusCode());

        // Verify the Content-Type header
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        // Verify the body of the response
        $this->assertEquals($expectedResult, $response->rawBody());
    }

    /**
     * Test the Warning method of JsonResponse.
     *
     * This method tests the functionality of the Warning method in the JsonResponse class.
     */
    public function testWarningResponse()
    {
        $errors = ['error' => 'something went wrong'];
        $response = JsonResponse::Warning($errors);

        $expectedResult = json_encode([
            'status' => 'warning',
            'data' => null,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE);

        // Verify the response object type
        $this->assertInstanceOf(Response::class, $response);

        // Verify the HTTP status code
        $this->assertEquals(400, $response->getStatusCode());

        // Verify the Content-Type header
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        // Verify the body of the response
        $this->assertEquals($expectedResult, $response->rawBody());
    }

    /**
     * Test the Error method of JsonResponse.
     *
     * This method tests the functionality of the Error method in the JsonResponse class.
     */
    public function testErrorResponse()
    {
        $errors = ['error' => 'internal server error'];
        $response = JsonResponse::Error($errors);

        $expectedResult = json_encode([
            'status' => 'error',
            'data' => null,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE);

        // Verify the response object type
        $this->assertInstanceOf(Response::class, $response);

        // Verify the HTTP status code
        $this->assertEquals(500, $response->getStatusCode());

        // Verify the Content-Type header
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        // Verify the body of the response
        $this->assertEquals($expectedResult, $response->rawBody());
    }
}
