<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report->title }} PDF</title>
    @php
        $logoPath = public_path('images/logo.png');
    @endphp
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.45; margin: 28px; }
        h1, h2, h3 { margin: 0; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 20px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-cell { width: 92px; }
        .brand-logo { width: 76px; height: auto; }
        .eyebrow { font-size: 10px; text-transform: uppercase; letter-spacing: 1.4px; color: #2563eb; font-weight: bold; }
        .title { font-size: 24px; font-weight: bold; margin-top: 4px; }
        .meta { margin-top: 6px; color: #475569; font-size: 11px; }
        .header-meta-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .header-meta-table td { width: 50%; padding: 6px 8px; border: 1px solid #e2e8f0; }
        .header-meta-label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; display: block; margin-bottom: 2px; }
        .section { margin-top: 22px; }
        .section-title { font-size: 15px; font-weight: bold; margin-bottom: 10px; color: #0f172a; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .stats { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .stats td { width: 33.33%; border: 1px solid #e2e8f0; padding: 10px; text-align: center; }
        .stats .count { font-size: 22px; font-weight: bold; display: block; }
        .muted { color: #64748b; }
        .small { font-size: 10px; }
        .structure-title { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .view-title { font-size: 12px; font-weight: bold; margin: 10px 0 6px; color: #1e293b; }
        .obs-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .obs-table th, .obs-table td { border: 1px solid #e2e8f0; padding: 7px 8px; vertical-align: top; }
        .obs-table th { background: #f8fafc; text-align: left; font-size: 10px; text-transform: uppercase; color: #64748b; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; }
        .major { background: #fee2e2; color: #b91c1c; }
        .moderate { background: #ffedd5; color: #c2410c; }
        .minor { background: #fef3c7; color: #a16207; }
        .media-list { margin: 6px 0 0 16px; padding: 0; }
        .media-list li { margin-bottom: 4px; }
        a { color: #2563eb; text-decoration: none; }
        .footer { margin-top: 28px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="brand-cell">
                    @if (file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Frogmen Technologies" class="brand-logo">
                    @endif
                </td>
                <td>
                    <div class="eyebrow">Frogmen Technologies</div>
                    <div class="title">{{ $report->title }}</div>
                    <div class="meta">
                        ROV Inspection Report
                        @if($project?->name)
                            | Project: {{ $project->name }}
                        @endif
                        @if($report->shared_date)
                            | Generated: {{ $report->shared_date->format('d M Y') }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="header-meta-table">
            <tr>
                <td>
                    <span class="header-meta-label">Created For</span>
                    {{ $project?->customer?->name ?? 'Client Not Set' }}
                </td>
                <td>
                    <span class="header-meta-label">Created By</span>
                    {{ $report->sharedBy?->name ?? 'FrogmenDash' }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="header-meta-label">Location</span>
                    {{ $project?->location ?? '—' }}
                </td>
                <td>
                    <span class="header-meta-label">Report Status</span>
                    {{ ucfirst((string) $report->status) ?: '—' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Summary</div>
        <div class="card">
            {{ $report->summary ?: 'No executive summary provided.' }}
        </div>
        <table class="stats">
            <tr>
                <td>
                    <span class="count" style="color:#b91c1c;">{{ $severityCounts['major'] }}</span>
                    <span class="small muted">Major</span>
                </td>
                <td>
                    <span class="count" style="color:#c2410c;">{{ $severityCounts['moderate'] }}</span>
                    <span class="small muted">Moderate</span>
                </td>
                <td>
                    <span class="count" style="color:#a16207;">{{ $severityCounts['minor'] }}</span>
                    <span class="small muted">Minor</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Project Details</div>
        <div class="card">
            <div><strong>Customer:</strong> {{ $project?->customer?->name ?? '—' }}</div>
            <div><strong>Project:</strong> {{ $project?->name ?? '—' }}</div>
            <div><strong>Location:</strong> {{ $project?->location ?? '—' }}</div>
            <div><strong>Coordinates:</strong>
                @if ($project?->latitude && $project?->longitude)
                    {{ $project->latitude }}, {{ $project->longitude }}
                @else
                    —
                @endif
            </div>
            <div><strong>Inspection Dates:</strong>
                @if ($project?->start_date)
                    {{ $project->start_date->format('d M Y') }}{{ $project->end_date ? ' - '.$project->end_date->format('d M Y') : '' }}
                @else
                    —
                @endif
            </div>
            @if (!empty($payload['project']['plan_view_url']))
                <div><strong>Plan View:</strong> <a href="{{ $payload['project']['plan_view_url'] }}">Open plan view</a></div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Structures, Views, and Observations</div>
        @forelse(($payload['project']['structures'] ?? []) as $structure)
            <div class="card">
                <div class="structure-title">{{ $structure['name'] }}</div>
                @if($structure['description'])
                    <div class="muted" style="margin-bottom: 6px;">{{ $structure['description'] }}</div>
                @endif
                @if($structure['diagram_url'])
                    <div class="small"><strong>Diagram:</strong> <a href="{{ $structure['diagram_url'] }}">Open structure diagram</a></div>
                @endif
                @if($structure['photo_url'])
                    <div class="small"><strong>Photo:</strong> <a href="{{ $structure['photo_url'] }}">Open structure photo</a></div>
                @endif

                @forelse($structure['views'] as $view)
                    <div class="view-title">{{ $view['name'] }} ({{ ucfirst($view['view_type']) }})</div>
                    @if(count($view['points']))
                        <table class="obs-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Severity</th>
                                    <th>Type / Description</th>
                                    <th>Location / Depth</th>
                                    <th>Media</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($view['points'] as $point)
                                    <tr>
                                        <td>{{ $point['observation_id'] ?: 'Point '.$point['point_number'] }}</td>
                                        <td>
                                            <span class="pill {{ strtolower($point['severity'] ?: 'minor') }}">
                                                {{ ucfirst($point['severity'] ?: 'minor') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div><strong>{{ $point['finding_type'] ?: '—' }}</strong></div>
                                            @if($point['description'])
                                                <div class="muted">{{ $point['description'] }}</div>
                                            @endif
                                            @if($point['dimension_mm'])
                                                <div class="small">Dimension: {{ $point['dimension_mm'] }}</div>
                                            @endif
                                            @if($point['recommendations'])
                                                <div class="small">Recommendation: {{ $point['recommendations'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $point['dive_location'] ?: '—' }}</div>
                                            <div class="small muted">Depth: {{ $point['depth_m'] ? $point['depth_m'].' m' : '—' }}</div>
                                            <div class="small muted">Pin: {{ $point['x_coordinate'] }}%, {{ $point['y_coordinate'] }}%</div>
                                        </td>
                                        <td>
                                            @if(count($point['media']))
                                                <ul class="media-list">
                                                    @foreach($point['media'] as $media)
                                                        <li>
                                                            {{ $media['file_name'] }} ({{ $media['media_type'] }})
                                                            @if(!empty($media['url']))
                                                                - <a href="{{ $media['url'] }}">Open media file</a>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="muted">No linked media</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="muted">No observations in this view.</div>
                    @endif
                @empty
                    <div class="muted">No views configured for this structure.</div>
                @endforelse

                @if(count($structure['unlinked_media']))
                    <div class="view-title">Unlinked Structure Media</div>
                    <ul class="media-list">
                        @foreach($structure['unlinked_media'] as $media)
                            <li>
                                {{ $media['file_name'] }} ({{ $media['media_type'] }})
                                @if(!empty($media['url']))
                                    - <a href="{{ $media['url'] }}">Open media file</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <div class="card muted">No structure data available for this report.</div>
        @endforelse
    </div>

    @if($report->conclusions)
        <div class="section">
            <div class="section-title">Conclusions</div>
            <div class="card">{{ $report->conclusions }}</div>
        </div>
    @endif

    @if($report->recommendations)
        <div class="section">
            <div class="section-title">Recommendations</div>
            <div class="card">{{ $report->recommendations }}</div>
        </div>
    @endif

    <div class="footer">
        Generated from FrogmenDash client report share link.
    </div>
</body>
</html>