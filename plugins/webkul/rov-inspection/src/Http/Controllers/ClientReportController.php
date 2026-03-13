<?php

namespace Webkul\RovInspection\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\RovInspection\Models\InspectionReport;
use Webkul\RovInspection\Models\ReportAccessLog;

class ClientReportController extends Controller
{
    public function show(Request $request, string $hash)
    {
        $report = InspectionReport::where('shared_link_hash', $hash)->firstOrFail();

        if ($report->shared_link_expires_at && $report->shared_link_expires_at->isPast()) {
            abort(410, 'This report link has expired.');
        }

        ReportAccessLog::create([
            'report_id'   => $report->id,
            'accessed_by' => $request->header('User-Agent'),
            'accessed_at' => now(),
            'ip_address'  => $request->ip(),
        ]);

        // Eager-load the full hierarchy: project → structures → views → points (with media)
        // and structure media (for the Inspection Data gallery)
        $project = $report->project()->withoutGlobalScopes()
            ->with([
                'structures' => function ($q) {
                    $q->orderBy('sort')->with([
                        'views.points.media',
                        'media' => fn ($q) => $q->whereNull('inspection_point_id'),
                    ]);
                },
                'customer',
            ])
            ->first();

        // Build flat severity counts for the Conclusions tab
        $severityCounts = ['major' => 0, 'moderate' => 0, 'minor' => 0];

        if ($project) {
            foreach ($project->structures as $structure) {
                foreach ($structure->views as $view) {
                    foreach ($view->points as $point) {
                        $key = strtolower($point->severity ?? '');
                        if (isset($severityCounts[$key])) {
                            $severityCounts[$key]++;
                        }
                    }
                }
            }
        }

        return view('rov-inspection::client.report', [
            'report'         => $report,
            'project'        => $project,
            'severityCounts' => $severityCounts,
        ]);
    }
}
