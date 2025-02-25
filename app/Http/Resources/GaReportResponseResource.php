<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Google\Service\AnalyticsData\RunReportResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class GaReportResponseResource extends JsonResource
{
    private const DATE_DIMENSIONS = [
        'date',
        'dateHour',
        'dateHourMinute',
    ];

    private array $metricTypes;

    public function __construct(RunReportResponse $resource, array $metricTypes = [])
    {
        parent::__construct($resource);

        $this->metricTypes = array_merge($this->getDefaultMetricTypes(), $metricTypes);
    }

    private function getDefaultMetricTypes(): array
    {
        return [
            // Numeric metrics
            'eventCount' => 'integer',
            'totalUsers' => 'integer',
            'activeUsers' => 'integer',
            'newUsers' => 'integer',
            'sessions' => 'integer',
            'conversions' => 'integer',
            'totalRevenue' => 'float',
            'averageSessionDuration' => 'float',
            'bounceRate' => 'float',
            'screenPageViews' => 'integer',
            'engagementRate' => 'float',
            'userEngagementDuration' => 'float',
            // Add more default metrics as needed
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->parseRows(),
            'meta' => [
                'dimensions' => $this->getDimensionsInfo(),
                'metrics' => $this->getMetricsInfo(),
                'rowCount' => $this->resource->getRowCount(),
                'totals' => $this->parseTotals(),
                'minimums' => $this->parseMinimums(),
                'maximums' => $this->parseMaximums(),
                'quota' => $this->getQuotaInfo(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ]
        ];
    }

    private function parseRows(): array
    {
        $dimensionHeaders = $this->getDimensionHeaders();
        $metricHeaders = $this->getMetricHeaders();

        return array_map(function ($row) use ($dimensionHeaders, $metricHeaders) {
            $rowData = [];

            // Parse dimensions
            foreach ($row->getDimensionValues() as $index => $dimension) {
                $headerName = $dimensionHeaders[$index];
                $cleanName = $this->cleanHeaderName($headerName);
                $value = $dimension->getValue();

                // Handle date dimensions
                if ($this->isDateDimension($headerName)) {
                    $value = $this->formatDateDimension($headerName, $value);
                }

                $rowData[$cleanName] = $value;
            }

            // Parse metrics
            foreach ($row->getMetricValues() as $index => $metric) {
                $headerName = $metricHeaders[$index];
                $rowData[$headerName] = $this->castMetricValue(
                    $metric->getValue(),
                    $headerName
                );
            }

            return $rowData;
        }, $this->resource->getRows() ?? []);
    }

    private function getDimensionsInfo(): array
    {
        $headers = $this->resource->getDimensionHeaders();

        return array_map(function ($header) {
            $name = $this->cleanHeaderName($header->getName());
            return [
                'name' => $name,
                'type' => $this->isDateDimension($header->getName()) ? 'DATE' : 'STRING',
                'api_name' => $header->getName(),
            ];
        }, $headers);
    }

    private function getMetricsInfo(): array
    {
        $headers = $this->resource->getMetricHeaders();

        return array_map(function ($header) {
            $name = $header->getName();
            return [
                'name' => $name,
                'type' => $this->metricTypes[$name] ?? 'string',
                'api_name' => $header->getName(),
            ];
        }, $headers);
    }

    private function getDimensionHeaders(): array
    {
        return array_map(
            fn($header) => $header->getName(),
            $this->resource->getDimensionHeaders()
        );
    }

    private function getMetricHeaders(): array
    {
        return array_map(
            fn($header) => $header->getName(),
            $this->resource->getMetricHeaders()
        );
    }

    private function cleanHeaderName(string $headerName): string
    {
        return str_replace(['customEvent:', 'customUser:'], '', $headerName);
    }

    private function isDateDimension(string $dimension): bool
    {
        return in_array($dimension, self::DATE_DIMENSIONS);
    }

    private function formatDateDimension(string $dimensionName, string $value): string|array
    {
        return match ($dimensionName) {
            'date' => Carbon::createFromFormat('Ymd', $value)->toIso8601String(),
            'dateHour' => Carbon::createFromFormat('YmdH', $value)->toIso8601String(),
            'dateHourMinute' => Carbon::createFromFormat('YmdHi', $value)->toIso8601String(),
            default => $value,
        };
    }

    private function castMetricValue(string $value, string $metricName): mixed
    {
        if (!isset($this->metricTypes[$metricName])) {
            return $value;
        }

        return match ($this->metricTypes[$metricName]) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => (bool) $value,
            default => $value,
        };
    }

    private function parseTotals(): ?array
    {
        if (!$this->resource->getTotals()) {
            return null;
        }

        $metricHeaders = $this->getMetricHeaders();
        $totals = [];

        foreach ($this->resource->getTotals() as $totalRow) {
            $metricValues = $totalRow->getMetricValues();
            foreach ($metricValues as $index => $metric) {
                $headerName = $metricHeaders[$index];
                $totals[$headerName] = $this->castMetricValue(
                    $metric->getValue(),
                    $headerName
                );
            }
        }

        return $totals;
    }

    private function parseMinimums(): ?array
    {
        if (!$this->resource->getMinimums()) {
            return null;
        }

        $metricHeaders = $this->getMetricHeaders();
        $minimums = [];

        foreach ($this->resource->getMinimums() as $minimumRow) {
            $metricValues = $minimumRow->getMetricValues();
            foreach ($metricValues as $index => $metric) {
                $headerName = $metricHeaders[$index];
                $minimums[$headerName] = $this->castMetricValue(
                    $metric->getValue(),
                    $headerName
                );
            }
        }

        return $minimums;
    }

    private function parseMaximums(): ?array
    {
        if (!$this->resource->getMaximums()) {
            return null;
        }

        $metricHeaders = $this->getMetricHeaders();
        $maximums = [];

        foreach ($this->resource->getMaximums() as $maximumRow) {
            $metricValues = $maximumRow->getMetricValues();
            foreach ($metricValues as $index => $metric) {
                $headerName = $metricHeaders[$index];
                $maximums[$headerName] = $this->castMetricValue(
                    $metric->getValue(),
                    $headerName
                );
            }
        }

        return $maximums;
    }

    private function getQuotaInfo(): ?array
    {
        $propertyQuota = $this->resource->getPropertyQuota();

        if (!$propertyQuota) {
            return null;
        }

        return [
            'tokensPerDay' => [
                'consumed' => $propertyQuota->getTokensPerDay()->getConsumed(),
                'remaining' => $propertyQuota->getTokensPerDay()->getRemaining(),
            ],
            'tokensPerHour' => [
                'consumed' => $propertyQuota->getTokensPerHour()->getConsumed(),
                'remaining' => $propertyQuota->getTokensPerHour()->getRemaining(),
            ],
            'concurrentRequests' => [
                'consumed' => $propertyQuota->getConcurrentRequests()->getConsumed(),
                'remaining' => $propertyQuota->getConcurrentRequests()->getRemaining(),
            ],
        ];
    }

}
