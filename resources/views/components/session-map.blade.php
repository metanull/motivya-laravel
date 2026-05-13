@props([
    'mapId' => 'session-map',
    'markers' => [],
    'fallbackCenter' => [4.3517, 50.8503],
    'height' => '400px',
    'singleMarker' => false,
])

@php
    $styleUrl = (string) config('maps.free.tile_style_url', 'https://tiles.openfreemap.org/styles/liberty');
@endphp

<div
    id="{{ $mapId }}"
    class="overflow-hidden rounded-lg shadow"
    style="height: {{ $height }};"
    x-data="sessionMap('{{ $mapId }}', {{ json_encode($markers) }}, {{ json_encode($fallbackCenter) }}, {{ json_encode($styleUrl) }})"
    x-init="initMap()"
    wire:ignore>
</div>

@once
    @push('scripts')
        @vite('resources/js/session-map.js')
    @endpush
@endonce
