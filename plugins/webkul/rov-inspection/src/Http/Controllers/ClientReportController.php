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

        $project = $report->project()->withoutGlobalScopes()->first();
        $points  = $project?->inspectionPoints()->with('media')->get() ?? collect();

        return view('rov-inspection::client.report', compact('report', 'project', 'points'));
    }
}
