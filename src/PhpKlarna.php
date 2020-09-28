<?php

namespace Vrajroham\PhpKlarna;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Vrajroham\PhpKlarna\Actions\ManageCustomerTokens;
use Vrajroham\PhpKlarna\Actions\ManageOrders;
use Vrajroham\PhpKlarna\Actions\ManagePayments;
use Vrajroham\PhpKlarna\Exceptions\ValidationException;

class PhpKlarna
{    
    use MakesHttpRequests;
    
    use ManagePayments, ManageCustomerTokens, ManageOrders;

    public Client $client;

    public function __construct(string $username, string $password, string $region = 'EU', string $mode = 'live')
    {        
        $base64Hash = base64_encode($username.':'.$password);

        $this->client = new Client([
            'base_uri' => $this->getBaseUri($region, $mode),
            'http_errors' => false,
            'verify' => false,
            'headers' => [
                'Authorization' => "Basic {$base64Hash}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    protected function transformCollection(array $collection, string $class): array
    {
        return array_map(function ($attributes) use ($class) {
            return new $class($attributes, $this);
        }, $collection);
    }

    public function convertDateFormat(string $date, $format = 'YmdHis'): string
    {
        return Carbon::parse($date)->format($format);
    }

    public function getBaseUri(string $region, string $mode)
    {
        $testModeBaseUrls = [
            'eu' => 'https://api.playground.klarna.com/',
            'na' => 'https://api-na.playground.klarna.com/',
            'oc' => 'https://api-oc.playground.klarna.com/'
        ];

        $liveModeBaseUrls = [
            'eu' => 'https://api.klarna.com/',
            'na' => 'https://api-na.klarna.com/',
            'oc' => 'https://api-oc.klarna.com/'
        ];
        
        if(!in_array(strtolower($region), ['eu', 'na', 'oc'])){
            throw new ValidationException(['Provided region is invalid. Must be EU or NA or OC']);
        }

        if(!in_array(strtolower($mode), ['test', 'live'])){
            throw new ValidationException(['Provide mode is invalid. Must be test or live']);
        }

        if(strcmp($mode, 'test') === 0){
            return $testModeBaseUrls[strtolower($region)];
        }

        return $liveModeBaseUrls[strtolower($region)];
    }
}
