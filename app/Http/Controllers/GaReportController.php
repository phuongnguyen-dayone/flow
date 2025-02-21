<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\GaReportResponseResource;
use App\Services\GoogleAnalyticsService;
use Illuminate\Http\Request;

class GaReportController extends Controller
{
    public function runTimeReport(GoogleAnalyticsService $gaService)
    {
        try {
            // Fetch recent Login events filtered by client_id
            $report = $gaService->getRealtimeEventData('1000014', 'login');

            return response()->json([
                'success' => true,
                'data' =>  GaReportResponseResource::make($report),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => json_decode($e->getMessage()),
            ], 500);
        }
    }
    public function report(GoogleAnalyticsService $gaService)
    {
        try {
            // Fetch recent Login events filtered by client_id
            $report = $gaService->getEventData('1000014', 'login');

            return response()->json([
                'success' => true,
                'data' => GaReportResponseResource::make($report),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => json_decode($e->getMessage()),
            ], 500);
        }

    }

}
