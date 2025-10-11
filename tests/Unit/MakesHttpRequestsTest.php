<?php

namespace Vrajroham\PhpKlarna\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use Vrajroham\PhpKlarna\Exceptions\FailedActionException;
use Vrajroham\PhpKlarna\Exceptions\NotFoundException;
use Vrajroham\PhpKlarna\Exceptions\ValidationException;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * @covers \Vrajroham\PhpKlarna\MakesHttpRequests
 */
class MakesHttpRequestsTest extends TestCase
{
    public function test_successful_get_request(): void
    {
        $responseData = ['test' => 'data'];
        $this->mockApiResponse($responseData);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint');

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('GET', $lastRequest->getMethod());
        $this->assertStringContainsString('test-endpoint', (string) $lastRequest->getUri());
    }

    public function test_successful_post_request_with_payload(): void
    {
        $responseData = ['created' => true];
        $payload = ['name' => 'test', 'value' => 123];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('post');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint', $payload);

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertStringContainsString('test-endpoint', (string) $lastRequest->getUri());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($payload, $requestBody);
    }

    public function test_successful_post_request_without_payload(): void
    {
        $responseData = ['success' => true];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('post');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint');

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertEmpty($lastRequest->getBody()->getContents());
    }

    public function test_successful_put_request(): void
    {
        $responseData = ['updated' => true];
        $payload = ['id' => 1, 'name' => 'updated'];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('put');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint', $payload);

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('PUT', $lastRequest->getMethod());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($payload, $requestBody);
    }

    public function test_successful_patch_request(): void
    {
        $responseData = ['patched' => true];
        $payload = ['field' => 'new_value'];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('patch');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint', $payload);

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('PATCH', $lastRequest->getMethod());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($payload, $requestBody);
    }

    public function test_successful_delete_request(): void
    {
        $responseData = ['deleted' => true];
        $payload = ['reason' => 'cleanup'];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint', $payload);

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEquals('DELETE', $lastRequest->getMethod());
        
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($payload, $requestBody);
    }

    public function test_validation_exception_on_422_status(): void
    {
        $errorData = [
            'error_code' => 'invalid_data',
            'error_messages' => ['Field is required', 'Invalid format']
        ];
        
        $this->mockApiError(422, $errorData);

        $this->expectException(ValidationException::class);
        
        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $method->invoke($this->klarna, 'test-endpoint');
    }

    public function test_not_found_exception_on_404_status(): void
    {
        $this->mockApiError(404);

        $this->expectException(NotFoundException::class);
        
        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $method->invoke($this->klarna, 'test-endpoint');
    }

    public function test_failed_action_exception_on_400_status(): void
    {
        $errorMessage = 'Bad request error';
        
        $this->mockApiError(400, ['message' => $errorMessage]);

        $this->expectException(FailedActionException::class);
        $this->expectExceptionMessage($errorMessage);
        
        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $method->invoke($this->klarna, 'test-endpoint');
    }

    public function test_generic_exception_on_500_status(): void
    {
        $errorMessage = 'Internal server error';
        
        $this->mockApiError(500, ['message' => $errorMessage]);

        $this->expectException(\Exception::class);
        
        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $method->invoke($this->klarna, 'test-endpoint');
    }

    public function test_is_successful_returns_true_for_2xx_status_codes(): void
    {
        $successfulCodes = [200, 201, 202, 204, 206];
        
        foreach ($successfulCodes as $code) {
            $response = new Response($code);
            $this->assertTrue($this->klarna->isSuccessful($response));
        }
    }

    public function test_is_successful_returns_false_for_non_2xx_status_codes(): void
    {
        $errorCodes = [400, 401, 403, 404, 422, 500, 502, 503];
        
        foreach ($errorCodes as $code) {
            $response = new Response($code);
            $this->assertFalse($this->klarna->isSuccessful($response));
        }
    }

    public function test_is_successful_returns_false_for_null_response(): void
    {
        $this->assertFalse($this->klarna->isSuccessful(null));
    }

    public function test_request_returns_string_for_non_json_response(): void
    {
        $textResponse = 'Plain text response';
        
        $this->mockHandler->append(new Response(200, [], $textResponse));

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint');

        $this->assertEquals($textResponse, $result);
    }

    public function test_request_handles_empty_response_body(): void
    {
        $this->mockHandler->append(new Response(200, [], ''));

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint');

        $this->assertEquals('', $result);
    }

    public function test_request_handles_malformed_json(): void
    {
        $malformedJson = '{"invalid": json}';
        
        $this->mockHandler->append(new Response(200, [], $malformedJson));

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint');

        $this->assertEquals($malformedJson, $result);
    }

    public function test_post_request_with_empty_array_payload(): void
    {
        $responseData = ['success' => true];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('post');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint', []);

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertEmpty($lastRequest->getBody()->getContents());
    }

    public function test_request_with_complex_payload(): void
    {
        $complexPayload = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'preferences' => [
                    'newsletter' => true,
                    'notifications' => false
                ]
            ],
            'metadata' => [
                'source' => 'api',
                'timestamp' => '2023-12-01T10:00:00Z'
            ]
        ];
        
        $responseData = ['created' => true, 'id' => 123];
        
        $this->mockApiResponse($responseData);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('post');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'complex-endpoint', $complexPayload);

        $this->assertEquals($responseData, $result);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals($complexPayload, $requestBody);
    }

    public function test_validation_exception_contains_error_data(): void
    {
        $errorData = [
            'error_code' => 'validation_failed',
            'error_messages' => [
                'purchase_country' => ['Field is required'],
                'order_amount' => ['Must be greater than 0']
            ]
        ];
        
        $this->mockApiError(422, $errorData);

        try {
            $reflection = new \ReflectionClass($this->klarna);
            $method = $reflection->getMethod('get');
            $method->setAccessible(true);
            $method->invoke($this->klarna, 'test-endpoint');
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertEquals($errorData, $e->getErrors());
        }
    }

    public function test_different_content_types_in_response(): void
    {
        $xmlResponse = '<?xml version="1.0"?><root><data>test</data></root>';
        
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/xml'], $xmlResponse));

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'test-endpoint');

        $this->assertEquals($xmlResponse, $result);
    }

    public function test_large_json_response_handling(): void
    {
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => str_repeat('Large description text ', 10),
                'metadata' => [
                    'created_at' => '2023-12-01T10:00:00Z',
                    'updated_at' => '2023-12-01T10:00:00Z'
                ]
            ];
        }
        
        $this->mockApiResponse(['items' => $largeData]);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('get');
        $method->setAccessible(true);
        $result = $method->invoke($this->klarna, 'large-data-endpoint');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(1000, $result['items']);
        $this->assertEquals('Item 999', $result['items'][999]['name']);
    }

    public function test_request_preserves_original_order_of_payload(): void
    {
        $orderedPayload = [
            'z_field' => 'last',
            'a_field' => 'first', 
            'm_field' => 'middle'
        ];
        
        $this->mockApiResponse(['success' => true]);

        $reflection = new \ReflectionClass($this->klarna);
        $method = $reflection->getMethod('post');
        $method->setAccessible(true);
        $method->invoke($this->klarna, 'order-test', $orderedPayload);
        
        $lastRequest = $this->mockHandler->getLastRequest();
        $requestBody = $lastRequest->getBody()->getContents();
        $decodedBody = json_decode($requestBody, true);
        
        $this->assertEquals($orderedPayload, $decodedBody);
    }

    /**
     * Protected methods testing via reflection or public proxy methods
     */
    public function test_all_http_verbs_call_request_method(): void
    {
        $methods = ['get', 'post', 'put', 'patch', 'delete'];
        
        foreach ($methods as $method) {
            $this->mockApiResponse(['method' => strtoupper($method)]);
            
            $reflection = new \ReflectionClass($this->klarna);
            $reflectionMethod = $reflection->getMethod($method);
            $reflectionMethod->setAccessible(true);
            
            if ($method === 'get') {
                $result = $reflectionMethod->invoke($this->klarna, 'test-endpoint');
            } else {
                $result = $reflectionMethod->invoke($this->klarna, 'test-endpoint', ['test' => 'data']);
            }
            
            $this->assertEquals(['method' => strtoupper($method)], $result);
            
            $lastRequest = $this->mockHandler->getLastRequest();
            $this->assertEquals(strtoupper($method), $lastRequest->getMethod());
        }
    }
}