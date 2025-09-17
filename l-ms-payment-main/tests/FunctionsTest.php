<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use support\Response;

/**
 * Class FunctionsTest
 *
 * Unit tests for the functions in app/functions.php.
 */
class FunctionsTest extends TestCase
{
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test the success function.
     *
     * This method tests the functionality of the success function in app/functions.php.
     */
    public function testSuccessFunction()
    {
        $data = ['key' => 'value'];
        $status = 200;
        $options = JSON_UNESCAPED_UNICODE;

        $response = success($data, $status, $options);

        $expectedResult = json_encode([
            'status' => 'success',
            'data' => $data,
            'errors' => null,
        ], $options);

        // Verify the response object type
        $this->assertInstanceOf(Response::class, $response);

        // Verify the HTTP status code
        $this->assertEquals($status, $response->getStatusCode());

        // Verify the Content-Type header
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        // Verify the body of the response
        $this->assertEquals($expectedResult, $response->rawBody());
    }

    /**
     * Test the warning function.
     *
     * This method tests the functionality of the warning function in app/functions.php.
     */
    public function testWarningFunction()
    {
        $errors = ['error' => 'something went wrong'];
        $status = 400;
        $options = JSON_UNESCAPED_UNICODE;

        $response = warning($errors, $status, $options);

        $expectedResult = json_encode([
            'status' => 'warning',
            'data' => null,
            'errors' => $errors,
        ], $options);

        // Verify the response object type
        $this->assertInstanceOf(Response::class, $response);

        // Verify the HTTP status code
        $this->assertEquals($status, $response->getStatusCode());

        // Verify the Content-Type header
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        // Verify the body of the response
        $this->assertEquals($expectedResult, $response->rawBody());
    }

    /**
     * Test the error function.
     *
     * This method tests the functionality of the error function in app/functions.php.
     */
    public function testErrorFunction()
    {
        $errors = ['error' => 'internal server error'];
        $status = 500;
        $options = JSON_UNESCAPED_UNICODE;

        $response = error($errors, $status, $options);

        $expectedResult = json_encode([
            'status' => 'error',
            'data' => null,
            'errors' => $errors,
        ], $options);

        // Verify the response object type
        $this->assertInstanceOf(Response::class, $response);

        // Verify the HTTP status code
        $this->assertEquals($status, $response->getStatusCode());

        // Verify the Content-Type header
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        // Verify the body of the response
        $this->assertEquals($expectedResult, $response->rawBody());
    }
}
