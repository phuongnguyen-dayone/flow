<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GoogleAnalyticsService;
use Illuminate\Http\Request;

class GaReportController extends Controller
{
    public function runTimeReport(GoogleAnalyticsService $gaService)
    {
        try {
            // Fetch recent Login events filtered by client_id
            $report = $gaService->getRealtimeEventData('1000014','Login');
            // Parse and format the report for display
            $data = [];
            foreach ($report->getRows() as $row) {
                $data[] = [
                    'event_name' => $row['dimensionValues'][0]['value'],
                    'client_id'  => $row['dimensionValues'][1]['value'],
                    'username'   => $row['dimensionValues'][2]['value'],
                    'event_count' => $row['metricValues'][0]['value'],
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

    }
    public function report(GoogleAnalyticsService $gaService)
    {
        try {
            // Fetch recent Login events filtered by client_id
            $report = $gaService->getEventData('1000014', 'Login');

            // Parse and format the report for display
            $data = [];
            foreach ($report->getRows() as $row) {
                $data[] = [
                    'event_name' => $row['dimensionValues'][0]['value'],
                    'client_id'  => $row['dimensionValues'][1]['value'],
                    'username'   => $row['dimensionValues'][2]['value'],
                    'event_count' => $row['metricValues'][0]['value'],
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

    }

}
