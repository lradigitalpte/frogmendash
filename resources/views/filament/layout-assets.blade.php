@php
    $viteManifest = public_path('build/manifest.json');
@endphp
@if(file_exists($viteManifest))
    @php
        $manifest = json_decode(file_get_contents($viteManifest), true) ?? [];
        $entries = ['resources/css/app.css', 'resources/js/app.js'];
    @endphp
    @foreach($entries as $entry)
        @if(isset($manifest[$entry]))
            @foreach($manifest[$entry]['css'] ?? [] as $css)
                <link rel="stylesheet" href="{{ asset('build/'.$css) }}">
            @endforeach
            @if(isset($manifest[$entry]['file']))
                @php $file = $manifest[$entry]['file']; @endphp
                @if(str_ends_with($file, '.css'))
                    <link rel="stylesheet" href="{{ asset('build/'.$file) }}">
                @else
                    <script type="module" src="{{ asset('build/'.$file) }}"></script>
                @endif
            @endif
        @endif
    @endforeach
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
