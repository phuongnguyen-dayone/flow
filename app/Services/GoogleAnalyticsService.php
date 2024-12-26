<?php

namespace App\Services;

use Google\Client;
use Google\Service\AnalyticsData;
use Google_Service_AnalyticsData_RunRealtimeReportRequest;
use Google_Service_AnalyticsData_RunReportRequest;

class GoogleAnalyticsService
{
    private AnalyticsData $analyticsData;
    private string $propertyId;
    private const API_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
    private const DIMENSIONS = [
        ['name' => 'eventName'],
        ['name' => 'customEvent:client_id'],
        ['name' => 'customEvent:username'],
    ];
    private const METRICS = [
        ['name' => 'eventCount']
    ];

    public function __construct()
    {
        $this->analyticsData = $this->initializeAnalyticsClient();
    }

    private function initializeAnalyticsClient(): AnalyticsData
    {
        $keyFilePath = config('services.google.credentials');
        $propertyId = config('services.google.property_id');

        $client = new Client();
        $client->setAuthConfig($keyFilePath);
        $client->addScope(self::API_SCOPE);

        $this->propertyId = $propertyId;
        return new AnalyticsData($client);
    }

    public function getRealtimeEventData(string $clientId, string $eventName = 'login', int $limit = 10)
    {
        $requestBody = $this->prepareRealtimeReportRequest($clientId, $eventName, $limit);

        return $this->analyticsData->properties->runRealtimeReport(
            "properties/{$this->propertyId}",
            $requestBody
        );
    }

    public function getEventData(string $clientId, string $eventName = 'login', int $limit = 10)
    {
        $requestBody = $this->prepareReportRequest($clientId, $eventName, $limit);

        return $this->analyticsData->properties->runReport(
            "properties/{$this->propertyId}",
            $requestBody
        );
    }

    private function prepareReportRequest(string $clientId, string $eventName, int $limit): Google_Service_AnalyticsData_RunReportRequest
    {
        return new Google_Service_AnalyticsData_RunReportRequest([
            'dimensions' => self::DIMENSIONS,
            'metrics' => self::METRICS,
            'dimensionFilter' => [
                'andGroup' => [
                    'filters' => [
                        [
                            'fieldName' => 'eventName',
                            'stringFilter' => ['value' => $eventName],
                        ],
                        [
                            'fieldName' => 'customEvent:client_id',
                            'stringFilter' => ['value' => $clientId],
                        ],
                    ],
                ],
            ],
            'limit' => $limit,
        ]);
    }
    private function prepareRealtimeReportRequest(string $clientId, string $eventName, int $limit): Google_Service_AnalyticsData_RunRealtimeReportRequest
    {
        return new Google_Service_AnalyticsData_RunRealtimeReportRequest([
            'dimensions' => self::DIMENSIONS,
            'metrics' => self::METRICS,
            'dimensionFilter' => [
                'andGroup' => [
                    'filters' => [
                        [
                            'fieldName' => 'eventName',
                            'stringFilter' => ['value' => $eventName],
                        ],
                        [
                            'fieldName' => 'customEvent:client_id',
                            'stringFilter' => ['value' => $clientId],
                        ],
                    ],
                ],
            ],
            'limit' => $limit,
        ]);
    }
}
