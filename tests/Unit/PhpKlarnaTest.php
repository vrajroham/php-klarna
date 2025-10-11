<?php

namespace Vrajroham\PhpKlarna\Tests\Unit;

use GuzzleHttp\Client;
use Vrajroham\PhpKlarna\Exceptions\ValidationException;
use Vrajroham\PhpKlarna\PhpKlarna;
use Vrajroham\PhpKlarna\Tests\TestCase;

/**
 * @covers \Vrajroham\PhpKlarna\PhpKlarna
 */
class PhpKlarnaTest extends TestCase
{
    public function test_constructor_initializes_with_valid_parameters(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $this->assertInstanceOf(PhpKlarna::class, $klarna);
        $this->assertInstanceOf(Client::class, $klarna->client);
    }

    public function test_constructor_sets_correct_base_uri_for_eu_test(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $expectedUri = 'https://api.playground.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('EU', 'test'));
    }

    public function test_constructor_sets_correct_base_uri_for_eu_live(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'live');
        
        $expectedUri = 'https://api.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('EU', 'live'));
    }

    public function test_constructor_sets_correct_base_uri_for_na_test(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'NA', 'test');
        
        $expectedUri = 'https://api-na.playground.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('NA', 'test'));
    }

    public function test_constructor_sets_correct_base_uri_for_na_live(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'NA', 'live');
        
        $expectedUri = 'https://api-na.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('NA', 'live'));
    }

    public function test_constructor_sets_correct_base_uri_for_oc_test(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'OC', 'test');
        
        $expectedUri = 'https://api-oc.playground.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('OC', 'test'));
    }

    public function test_constructor_sets_correct_base_uri_for_oc_live(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'OC', 'live');
        
        $expectedUri = 'https://api-oc.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('OC', 'live'));
    }

    public function test_constructor_throws_exception_for_invalid_region(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Provided region is invalid. Must be EU or NA or OC');
        
        new PhpKlarna('username', 'password', 'INVALID', 'test');
    }

    public function test_constructor_throws_exception_for_invalid_mode(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Provide mode is invalid. Must be test or live');
        
        new PhpKlarna('username', 'password', 'EU', 'invalid');
    }

    public function test_constructor_throws_exception_for_lowercase_invalid_region(): void
    {
        $this->expectException(ValidationException::class);
        
        new PhpKlarna('username', 'password', 'invalid', 'test');
    }

    public function test_constructor_accepts_lowercase_valid_region(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'eu', 'test');
        
        $expectedUri = 'https://api.playground.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('eu', 'test'));
    }

    public function test_constructor_accepts_lowercase_valid_mode(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'live');
        
        $expectedUri = 'https://api.klarna.com/';
        $this->assertEquals($expectedUri, $klarna->getBaseUri('EU', 'live'));
    }

    public function test_set_client_updates_http_client(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        $originalClient = $klarna->client;
        
        $newClient = new Client();
        $result = $klarna->setClient($newClient);
        
        $this->assertSame($klarna, $result);
        $this->assertSame($newClient, $klarna->client);
        $this->assertNotSame($originalClient, $klarna->client);
    }

    public function test_get_base_uri_returns_correct_urls(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        // Test all combinations
        $this->assertEquals('https://api.playground.klarna.com/', $klarna->getBaseUri('EU', 'test'));
        $this->assertEquals('https://api.klarna.com/', $klarna->getBaseUri('EU', 'live'));
        $this->assertEquals('https://api-na.playground.klarna.com/', $klarna->getBaseUri('NA', 'test'));
        $this->assertEquals('https://api-na.klarna.com/', $klarna->getBaseUri('NA', 'live'));
        $this->assertEquals('https://api-oc.playground.klarna.com/', $klarna->getBaseUri('OC', 'test'));
        $this->assertEquals('https://api-oc.klarna.com/', $klarna->getBaseUri('OC', 'live'));
    }

    public function test_convert_date_format_with_default_format(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $result = $klarna->convertDateFormat('2023-12-25 15:30:45');
        
        $this->assertEquals('20231225153045', $result);
    }

    public function test_convert_date_format_with_custom_format(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $result = $klarna->convertDateFormat('2023-12-25 15:30:45', 'Y-m-d\TH:i:s\Z');
        
        $this->assertEquals('2023-12-25T15:30:45Z', $result);
    }

    public function test_convert_date_format_with_different_input_formats(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $result1 = $klarna->convertDateFormat('2023/12/25');
        $result2 = $klarna->convertDateFormat('December 25, 2023');
        
        $this->assertEquals('20231225000000', $result1);
        $this->assertEquals('20231225000000', $result2);
    }

    public function test_transform_collection_creates_resource_instances(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $collection = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];
        
        $reflection = new \ReflectionClass($klarna);
        $method = $reflection->getMethod('transformCollection');
        $method->setAccessible(true);
        $result = $method->invoke($klarna, $collection, \Vrajroham\PhpKlarna\Resources\ApiResource::class);
        
        $this->assertCount(2, $result);
        $this->assertInstanceOf(\Vrajroham\PhpKlarna\Resources\ApiResource::class, $result[0]);
        $this->assertInstanceOf(\Vrajroham\PhpKlarna\Resources\ApiResource::class, $result[1]);
    }

    public function test_http_client_has_correct_headers(): void
    {
        $klarna = new PhpKlarna('test-user', 'test-pass', 'EU', 'test');
        
        $config = $klarna->client->getConfig();
        $headers = $config['headers'];
        
        $expectedAuth = 'Basic ' . base64_encode('test-user:test-pass');
        
        $this->assertEquals($expectedAuth, $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Accept']);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    public function test_http_client_has_correct_base_uri(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'NA', 'live');
        
        $config = $klarna->client->getConfig();
        
        $this->assertEquals('https://api-na.klarna.com/', (string) $config['base_uri']);
    }

    public function test_http_client_ssl_verification_disabled(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $config = $klarna->client->getConfig();
        
        $this->assertFalse($config['verify']);
    }

    public function test_http_client_http_errors_disabled(): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', 'test');
        
        $config = $klarna->client->getConfig();
        
        $this->assertFalse($config['http_errors']);
    }

    /**
     * @dataProvider regionProvider
     */
    public function test_region_case_insensitive(string $region, string $expectedLower): void
    {
        $klarna = new PhpKlarna('username', 'password', $region, 'test');
        
        $testUri = $klarna->getBaseUri($expectedLower, 'test');
        $regionUri = $klarna->getBaseUri($region, 'test');
        
        $this->assertEquals($testUri, $regionUri);
    }

    public function regionProvider(): array
    {
        return [
            ['EU', 'eu'],
            ['eu', 'eu'],
            ['NA', 'na'],
            ['na', 'na'],
            ['OC', 'oc'],
            ['oc', 'oc'],
        ];
    }

    /**
     * @dataProvider modeProvider
     */
    public function test_mode_case_insensitive(string $mode): void
    {
        $klarna = new PhpKlarna('username', 'password', 'EU', $mode);
        
        $uri = $klarna->getBaseUri('EU', $mode);
        
        if (strtolower($mode) === 'test') {
            $this->assertStringContainsString('playground', $uri);
        } elseif (strtolower($mode) === 'live') {
            $this->assertStringNotContainsString('playground', $uri);
        }
    }

    public function modeProvider(): array
    {
        return [
            ['test'],
            ['live'],
            ['TEST'],
            ['LIVE'],
        ];
    }
}