<?php

namespace Vrajroham\PhpKlarna\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use Vrajroham\PhpKlarna\PhpKlarna;
use Vrajroham\PhpKlarna\Resources\ApiResource;

/**
 * @covers \Vrajroham\PhpKlarna\Resources\ApiResource
 */
class ApiResourceTest extends TestCase
{
    public function test_constructor_sets_attributes_and_klarna_instance(): void
    {
        $attributes = ['test_key' => 'test_value', 'another_key' => 'another_value'];
        $klarna = $this->createMock(PhpKlarna::class);

        $resource = new ApiResource($attributes, $klarna);

        $this->assertEquals($attributes, $resource->attributes);
        $this->assertSame($klarna, $this->getPrivateProperty($resource, 'klarna'));
    }

    public function test_constructor_without_klarna_instance(): void
    {
        $attributes = ['test_key' => 'test_value'];

        $resource = new ApiResource($attributes);

        $this->assertEquals($attributes, $resource->attributes);
        $this->assertNull($this->getPrivateProperty($resource, 'klarna'));
    }

    public function test_fill_method_converts_snake_case_to_camel_case(): void
    {
        $attributes = [
            'simple_key' => 'simple_value',
            'complex_snake_case_key' => 'complex_value',
            'another_test_key' => 'another_value',
            'single' => 'single_value'
        ];

        $resource = new ApiResource($attributes);

        $this->assertEquals('simple_value', $resource->simpleKey);
        $this->assertEquals('complex_value', $resource->complexSnakeCaseKey);
        $this->assertEquals('another_value', $resource->anotherTestKey);
        $this->assertEquals('single_value', $resource->single);
    }

    public function test_camel_case_conversion(): void
    {
        $testCases = [
            'simple_key' => 'simpleKey',
            'complex_snake_case_key' => 'complexSnakeCaseKey',
            'key_with_numbers_123' => 'keyWithNumbers123',
            'single' => 'single',
            'a_b_c_d_e' => 'aBCDE',
            'test_key_end' => 'testKeyEnd'
        ];

        foreach ($testCases as $snakeCase => $expectedCamelCase) {
            $attributes = [$snakeCase => 'test_value'];
            $resource = new ApiResource($attributes);
            
            $this->assertTrue(property_exists($resource, $expectedCamelCase), 
                "Property {$expectedCamelCase} should exist for input {$snakeCase}");
            $this->assertEquals('test_value', $resource->{$expectedCamelCase});
        }
    }

    public function test_fill_method_handles_various_data_types(): void
    {
        $attributes = [
            'string_value' => 'test string',
            'integer_value' => 12345,
            'float_value' => 123.45,
            'boolean_true' => true,
            'boolean_false' => false,
            'null_value' => null,
            'array_value' => ['item1', 'item2', 'item3'],
            'nested_array' => [
                'nested_key' => 'nested_value',
                'nested_number' => 999
            ]
        ];

        $resource = new ApiResource($attributes);

        $this->assertEquals('test string', $resource->stringValue);
        $this->assertEquals(12345, $resource->integerValue);
        $this->assertEquals(123.45, $resource->floatValue);
        $this->assertTrue($resource->booleanTrue);
        $this->assertFalse($resource->booleanFalse);
        $this->assertNull($resource->nullValue);
        $this->assertEquals(['item1', 'item2', 'item3'], $resource->arrayValue);
        $this->assertEquals(['nested_key' => 'nested_value', 'nested_number' => 999], $resource->nestedArray);
    }

    public function test_sleep_method_excludes_klarna_and_attributes(): void
    {
        $attributes = [
            'public_property' => 'public_value',
            'another_public_property' => 'another_value'
        ];
        $klarna = $this->createMock(PhpKlarna::class);

        $resource = new ApiResource($attributes, $klarna);

        $sleepProperties = $resource->__sleep();

        $this->assertContains('publicProperty', $sleepProperties);
        $this->assertContains('anotherPublicProperty', $sleepProperties);
        $this->assertNotContains('klarna', $sleepProperties);
        $this->assertNotContains('attributes', $sleepProperties);
    }

    public function test_sleep_method_with_no_public_properties(): void
    {
        $resource = new ApiResource([]);

        $sleepProperties = $resource->__sleep();

        $this->assertIsArray($sleepProperties);
        $this->assertEmpty($sleepProperties);
    }

    public function test_resource_with_empty_attributes(): void
    {
        $resource = new ApiResource([]);

        $this->assertEquals([], $resource->attributes);
        $this->assertNull($this->getPrivateProperty($resource, 'klarna'));
    }

    public function test_resource_with_special_characters_in_keys(): void
    {
        $attributes = [
            'key_with_123_numbers' => 'numeric_value',
            'key_with_special_chars' => 'special_value'
        ];

        $resource = new ApiResource($attributes);

        $this->assertEquals('numeric_value', $resource->keyWith123Numbers);
        $this->assertEquals('special_value', $resource->keyWithSpecialChars);
    }

    public function test_camel_case_with_edge_cases(): void
    {
        $testCases = [
            'trailing_underscore_' => 'trailingUnderscore',
            'multiple___underscores' => 'multipleUnderscores',
            'a' => 'a'
        ];

        foreach ($testCases as $input => $expected) {
            $attributes = [$input => 'test'];
            $resource = new ApiResource($attributes);
            
            $this->assertTrue(property_exists($resource, $expected), 
                "Property {$expected} should exist for input '{$input}'");
        }
    }

    public function test_resource_immutability_of_original_attributes(): void
    {
        $originalAttributes = [
            'test_key' => 'original_value',
            'another_key' => 'another_original_value'
        ];

        $resource = new ApiResource($originalAttributes);

        // Modify original array
        $originalAttributes['test_key'] = 'modified_value';
        $originalAttributes['new_key'] = 'new_value';

        // Resource should still have original values
        $this->assertEquals('original_value', $resource->testKey);
        $this->assertEquals('another_original_value', $resource->anotherKey);
        $this->assertFalse(property_exists($resource, 'newKey'));
    }

    public function test_klarna_instance_preservation(): void
    {
        $klarna = $this->createMock(PhpKlarna::class);
        $attributes = ['test' => 'value'];

        $resource = new ApiResource($attributes, $klarna);

        $this->assertSame($klarna, $this->getPrivateProperty($resource, 'klarna'));
    }

    public function test_attributes_are_accessible(): void
    {
        $attributes = [
            'order_id' => 'order_123',
            'status' => 'completed',
            'amount' => 10000
        ];

        $resource = new ApiResource($attributes);

        $this->assertEquals($attributes, $resource->attributes);
        $this->assertEquals('order_123', $resource->attributes['order_id']);
        $this->assertEquals('completed', $resource->attributes['status']);
        $this->assertEquals(10000, $resource->attributes['amount']);
    }

    public function test_property_overwriting_with_same_key(): void
    {
        $attributes = [
            'test_key' => 'first_value'
        ];

        $resource = new ApiResource($attributes);
        $this->assertEquals('first_value', $resource->testKey);

        // Manually set the property to test it's overwritten
        $resource->testKey = 'modified_value';
        $this->assertEquals('modified_value', $resource->testKey);

        // Original attributes should remain unchanged
        $this->assertEquals('first_value', $resource->attributes['test_key']);
    }

    public function test_complex_nested_data_structures(): void
    {
        $attributes = [
            'customer_info' => [
                'billing_address' => [
                    'street_name' => 'Main Street',
                    'house_number' => '123'
                ],
                'contact_info' => [
                    'email' => 'test@example.com',
                    'phone' => '+1234567890'
                ]
            ],
            'order_lines' => [
                [
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'unit_price' => 1000
                ],
                [
                    'product_name' => 'Product 2', 
                    'quantity' => 1,
                    'unit_price' => 2000
                ]
            ]
        ];

        $resource = new ApiResource($attributes);

        $this->assertEquals($attributes['customer_info'], $resource->customerInfo);
        $this->assertEquals('Main Street', $resource->customerInfo['billing_address']['street_name']);
        $this->assertEquals($attributes['order_lines'], $resource->orderLines);
        $this->assertEquals('Product 1', $resource->orderLines[0]['product_name']);
        $this->assertEquals(2, $resource->orderLines[0]['quantity']);
    }

    /**
     * Helper method to access private properties for testing
     */
    private function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}