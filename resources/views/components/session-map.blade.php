@props([
    'mapId' => 'session-map',
    'markers' => [],
    'fallbackCenter' => [4.3517, 50.8503],
    'height' => '400px',
    'singleMarker' => false,
])

<div
    id="{{ $mapId }}"
    class="overflow-hidden rounded-lg shadow"
    style="height: {{ $height }};"
    x-data="sessionMap('{{ $mapId }}', {{ json_encode($markers) }}, {{ json_encode($fallbackCenter) }})"
    x-init="initMap()"
    wire:ignore>
</div>

@once
    @push('scripts')
        @vite('resources/js/session-map.js')
    @endpush
@endonce
