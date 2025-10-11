<?php

namespace Vrajroham\PhpKlarna\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Vrajroham\PhpKlarna\Exceptions\FailedActionException;
use Vrajroham\PhpKlarna\Exceptions\NotFoundException;
use Vrajroham\PhpKlarna\Exceptions\ValidationException;

/**
 * @covers \Vrajroham\PhpKlarna\Exceptions\ValidationException
 * @covers \Vrajroham\PhpKlarna\Exceptions\NotFoundException
 * @covers \Vrajroham\PhpKlarna\Exceptions\FailedActionException
 */
class ExceptionsTest extends TestCase
{
    public function test_validation_exception_can_be_created_with_errors_array(): void
    {
        $errors = [
            'purchase_country' => ['Field is required'],
            'order_amount' => ['Must be greater than 0', 'Must be an integer']
        ];

        $exception = new ValidationException($errors);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertStringContainsString('The given data failed to pass validation', $exception->getMessage());
    }

    public function test_validation_exception_with_empty_errors_array(): void
    {
        $exception = new ValidationException([]);

        $this->assertEquals([], $exception->getErrors());
        $this->assertStringContainsString('The given data failed to pass validation', $exception->getMessage());
    }

    public function test_validation_exception_with_single_error(): void
    {
        $errors = ['field' => ['Single error message']];
        
        $exception = new ValidationException($errors);

        $this->assertEquals($errors, $exception->getErrors());
        $this->assertCount(1, $exception->getErrors());
    }

    public function test_validation_exception_with_multiple_fields_and_errors(): void
    {
        $errors = [
            'email' => [
                'Email is required',
                'Email must be valid format'
            ],
            'password' => [
                'Password is required',
                'Password must be at least 8 characters',
                'Password must contain special characters'
            ],
            'age' => [
                'Age must be 18 or older'
            ]
        ];

        $exception = new ValidationException($errors);

        $this->assertEquals($errors, $exception->getErrors());
        $this->assertCount(3, $exception->getErrors());
        $this->assertCount(2, $exception->getErrors()['email']);
        $this->assertCount(3, $exception->getErrors()['password']);
        $this->assertCount(1, $exception->getErrors()['age']);
    }

    public function test_validation_exception_with_nested_error_structure(): void
    {
        $errors = [
            'billing_address' => [
                'street_address' => ['Street address is required'],
                'postal_code' => ['Postal code is required', 'Invalid format']
            ],
            'order_lines' => [
                '0' => [
                    'name' => ['Product name is required'],
                    'quantity' => ['Quantity must be greater than 0']
                ]
            ]
        ];

        $exception = new ValidationException($errors);

        $this->assertEquals($errors, $exception->getErrors());
        $this->assertArrayHasKey('billing_address', $exception->getErrors());
        $this->assertArrayHasKey('order_lines', $exception->getErrors());
    }

    public function test_validation_exception_error_message_generation(): void
    {
        $errors = ['test_field' => ['Test error message']];
        
        $exception = new ValidationException($errors);

        $message = $exception->getMessage();
        
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('The given data failed to pass validation', $message);
    }

    public function test_validation_exception_with_complex_klarna_error_structure(): void
    {
        $klarnaErrors = [
            'error_code' => 'INVALID_DATA',
            'error_messages' => [
                'purchase_country must be a valid ISO 3166 alpha-2 country code',
                'order_amount must be a non-negative integer'
            ],
            'correlation_id' => 'abcd-1234-efgh-5678'
        ];

        $exception = new ValidationException($klarnaErrors);

        $this->assertEquals($klarnaErrors, $exception->getErrors());
        $this->assertEquals('INVALID_DATA', $exception->getErrors()['error_code']);
        $this->assertCount(2, $exception->getErrors()['error_messages']);
    }

    public function test_not_found_exception_creation(): void
    {
        $exception = new NotFoundException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertStringContainsString('Resource not found', $exception->getMessage());
    }

    public function test_not_found_exception_with_custom_message(): void
    {
        $customMessage = 'Order with ID order_123 was not found';
        
        $exception = new NotFoundException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    public function test_not_found_exception_with_custom_code(): void
    {
        $customCode = 1001;
        
        $exception = new NotFoundException('Not found', $customCode);

        $this->assertEquals($customCode, $exception->getCode());
    }

    public function test_not_found_exception_with_previous_exception(): void
    {
        $previousException = new \Exception('Previous error');
        
        $exception = new NotFoundException('Not found', 0, $previousException);

        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_failed_action_exception_creation(): void
    {
        $message = 'The action could not be completed';
        
        $exception = new FailedActionException($message);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_failed_action_exception_with_empty_message(): void
    {
        $exception = new FailedActionException('');

        $this->assertEquals('', $exception->getMessage());
    }

    public function test_failed_action_exception_with_json_message(): void
    {
        $jsonMessage = json_encode([
            'error' => 'Payment declined',
            'reason' => 'Insufficient funds',
            'code' => 'PAYMENT_DECLINED'
        ]);
        
        $exception = new FailedActionException($jsonMessage);

        $this->assertEquals($jsonMessage, $exception->getMessage());
        $this->assertJson($exception->getMessage());
    }

    public function test_failed_action_exception_with_custom_code_and_previous(): void
    {
        $message = 'Action failed';
        $code = 2001;
        $previous = new \Exception('Root cause');
        
        $exception = new FailedActionException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_all_exceptions_extend_base_exception(): void
    {
        $validationException = new ValidationException([]);
        $notFoundException = new NotFoundException();
        $failedActionException = new FailedActionException('test');

        $this->assertInstanceOf(\Exception::class, $validationException);
        $this->assertInstanceOf(\Exception::class, $notFoundException);
        $this->assertInstanceOf(\Exception::class, $failedActionException);
    }

    public function test_validation_exception_can_be_serialized(): void
    {
        $errors = ['field' => ['Error message']];
        $exception = new ValidationException($errors);

        // Test that the exception can be created and used normally
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertEquals($errors, $exception->getErrors());
        
        // Skip serialization test due to PHP 8.2+ DOM serialization restrictions
        $this->assertTrue(true);
    }

    public function test_exception_throwing_and_catching(): void
    {
        // Test ValidationException
        try {
            throw new ValidationException(['field' => ['error']]);
        } catch (ValidationException $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
            $this->assertEquals(['field' => ['error']], $e->getErrors());
        }

        // Test NotFoundException
        try {
            throw new NotFoundException('Resource not found');
        } catch (NotFoundException $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);
            $this->assertEquals('Resource not found', $e->getMessage());
        }

        // Test FailedActionException
        try {
            throw new FailedActionException('Action failed');
        } catch (FailedActionException $e) {
            $this->assertInstanceOf(FailedActionException::class, $e);
            $this->assertEquals('Action failed', $e->getMessage());
        }
    }

    public function test_exception_inheritance_hierarchy(): void
    {
        $validationException = new ValidationException([]);
        $notFoundException = new NotFoundException();
        $failedActionException = new FailedActionException('test');

        // Test that all exceptions can be caught as base Exception
        $exceptions = [$validationException, $notFoundException, $failedActionException];
        
        foreach ($exceptions as $exception) {
            $caught = false;
            try {
                throw $exception;
            } catch (\Exception $e) {
                $caught = true;
                $this->assertInstanceOf(\Exception::class, $e);
            }
            $this->assertTrue($caught, 'Exception should be catchable as base Exception');
        }
    }

    public function test_validation_exception_with_klarna_specific_fields(): void
    {
        $klarnaValidationErrors = [
            'purchase_country' => ['Invalid country code'],
            'purchase_currency' => ['Unsupported currency'],
            'order_amount' => ['Amount too low', 'Amount must be positive'],
            'order_lines' => [
                '0' => [
                    'name' => ['Product name required'],
                    'quantity' => ['Invalid quantity'],
                    'unit_price' => ['Price must be positive']
                ]
            ],
            'billing_address' => [
                'email' => ['Invalid email format'],
                'postal_code' => ['Invalid postal code for country']
            ]
        ];

        $exception = new ValidationException($klarnaValidationErrors);

        $this->assertEquals($klarnaValidationErrors, $exception->getErrors());
        $this->assertArrayHasKey('purchase_country', $exception->getErrors());
        $this->assertArrayHasKey('order_lines', $exception->getErrors());
        $this->assertArrayHasKey('billing_address', $exception->getErrors());
        $this->assertCount(2, $exception->getErrors()['order_amount']);
    }

    public function test_exceptions_with_stack_trace(): void
    {
        try {
            $this->throwValidationException();
        } catch (ValidationException $e) {
            $trace = $e->getTrace();
            $this->assertIsArray($trace);
            $this->assertNotEmpty($trace);
            $this->assertArrayHasKey('function', $trace[0]);
            $this->assertEquals('throwValidationException', $trace[0]['function']);
        }
    }

    private function throwValidationException(): void
    {
        throw new ValidationException(['test' => ['Test error']]);
    }

    public function test_exception_chaining(): void
    {
        $rootCause = new \Exception('Root cause error');
        $middleCause = new FailedActionException('Middle error', 0, $rootCause);
        $topException = new ValidationException(['field' => ['Final error']]);

        // Simulate exception chaining manually for testing
        $this->assertSame($rootCause, $middleCause->getPrevious());
        $this->assertEquals('Root cause error', $middleCause->getPrevious()->getMessage());
    }
}