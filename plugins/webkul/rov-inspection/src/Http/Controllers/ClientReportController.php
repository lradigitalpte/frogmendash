<?php

namespace Webkul\RovInspection\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Webkul\RovInspection\Enums\Severity;
use Webkul\RovInspection\Models\InspectionReport;
use Webkul\RovInspection\Models\ReportAccessLog;
use Webkul\Security\Models\Scopes\CompanyScope;

class ClientReportController extends Controller
{
    public function show(Request $request, string $hash)
    {
        [$report, $project, $severityCounts] = $this->loadReportContext($request, $hash);

        return view('rov-inspection::client.report', [
            'report'         => $report,
            'project'        => $project,
            'severityCounts' => $severityCounts,
        ]);
    }

    public function data(Request $request, string $hash): JsonResponse
    {
        [$report, $project, $severityCounts] = $this->loadReportContext($request, $hash);

        return response()->json($this->buildReportPayload($report, $project, $severityCounts));
    }

    public function downloadPdf(Request $request, string $hash): Response
    {
        [$report, $project, $severityCounts] = $this->loadReportContext($request, $hash);

        abort_unless($report->client_can_download, 403, 'PDF download is not enabled for this report.');

        $pdf = Pdf::loadView('rov-inspection::client.report-pdf', [
            'report'         => $report,
            'project'        => $project,
            'severityCounts' => $severityCounts,
            'payload'        => $this->buildReportPayload($report, $project, $severityCounts),
        ])->setPaper('a4', 'portrait');

        $fileName = str($report->title ?: 'inspection-report')
            ->slug('-')
            ->append('.pdf')
            ->toString();

        return $pdf->download($fileName);
    }

    private function loadReportContext(Request $request, string $hash): array
    {
        // Public, hash-gated endpoint: bypass tenant scoping so the shared link
        // resolves without an authenticated user (access is controlled by the hash).
        $report = InspectionReport::withoutGlobalScope(CompanyScope::class)
            ->where('shared_link_hash', $hash)
            ->firstOrFail();

        if ($report->shared_link_expires_at && $report->shared_link_expires_at->isPast()) {
            abort(410, 'This report link has expired.');
        }

        ReportAccessLog::create([
            'report_id'   => $report->id,
            'accessed_by' => $request->header('User-Agent'),
            'accessed_at' => now(),
            'ip_address'  => $request->ip(),
        ]);

        $project = $report->project()->withoutGlobalScopes()
            ->with([
                'structures' => function ($query) {
                    $query->orderBy('sort')->with([
                        'views.points.media',
                        'media' => fn ($mediaQuery) => $mediaQuery->whereNull('inspection_point_id'),
                    ]);
                },
                'customer',
            ])
            ->first();

        $severityCounts = ['major' => 0, 'moderate' => 0, 'minor' => 0];

        if ($project) {
            foreach ($project->structures as $structure) {
                foreach ($structure->views as $view) {
                    foreach ($view->points as $point) {
                        $normalizedSeverity = Severity::normalize($point->severity ?? '') ?? 'minor';
                        $point->severity = $normalizedSeverity;
                        $key = $normalizedSeverity;

                        if (isset($severityCounts[$key])) {
                            $severityCounts[$key]++;
                        }
                    }
                }
            }
        }

        return [$report, $project, $severityCounts];
    }

    private function buildReportPayload(InspectionReport $report, $project, array $severityCounts): array
    {
        return [
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'summary' => $report->summary,
                'full_report' => $report->full_report,
                'conclusions' => $report->conclusions,
                'recommendations' => $report->recommendations,
                'status' => $report->status,
                'shared_date' => optional($report->shared_date)?->toIso8601String(),
                'expires_at' => optional($report->shared_link_expires_at)?->toIso8601String(),
                'client_can_download' => $report->client_can_download,
                'client_can_print' => $report->client_can_print,
            ],
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->location,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'start_date' => optional($project->start_date)?->toDateString(),
                'end_date' => optional($project->end_date)?->toDateString(),
                'plan_view_url' => $project->plan_view_path ? \Illuminate\Support\Facades\Storage::disk('s3')->url($project->plan_view_path) : null,
                'customer' => $project->customer ? [
                    'id' => $project->customer->id,
                    'name' => $project->customer->name,
                    'image_url' => $project->customer->image_url,
                ] : null,
                'structures' => $project->structures->map(function ($structure) {
                    return [
                        'id' => $structure->id,
                        'name' => $structure->name,
                        'description' => $structure->description,
                        'photo_url' => $structure->photo_path ? \Illuminate\Support\Facades\Storage::disk('s3')->url($structure->photo_path) : null,
                        'diagram_url' => $structure->diagram_path ? \Illuminate\Support\Facades\Storage::disk('s3')->url($structure->diagram_path) : null,
                        'views' => $structure->views->map(function ($view) {
                            return [
                                'id' => $view->id,
                                'name' => $view->name,
                                'view_type' => $view->view_type,
                                'points' => $view->points->map(function ($point) {
                                    return [
                                        'id' => $point->id,
                                        'observation_id' => $point->observation_id,
                                        'point_number' => $point->point_number,
                                        'severity' => Severity::normalize($point->severity) ?? 'minor',
                                        'finding_type' => $point->finding_type,
                                        'description' => $point->description,
                                        'dive_location' => $point->dive_location,
                                        'depth_m' => $point->depth_m,
                                        'dimension_mm' => $point->dimension_mm,
                                        'recommendations' => $point->recommendations,
                                        'x_coordinate' => $point->x_coordinate,
                                        'y_coordinate' => $point->y_coordinate,
                                        'media' => $point->media->map(fn ($media) => [
                                            'id' => $media->id,
                                            'file_name' => $media->file_name,
                                            'media_type' => $media->media_type,
                                            'url' => $media->url,
                                            'thumbnail_url' => $media->thumbnail_url,
                                        ])->values()->all(),
                                    ];
                                })->values()->all(),
                            ];
                        })->values()->all(),
                        'unlinked_media' => $structure->media->map(fn ($media) => [
                            'id' => $media->id,
                            'file_name' => $media->file_name,
                            'media_type' => $media->media_type,
                            'url' => $media->url,
                            'thumbnail_url' => $media->thumbnail_url,
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ] : null,
            'severity_counts' => $severityCounts,
        ];
    }
}
