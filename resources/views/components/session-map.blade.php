@props(['markers'])

<div
    id="session-map"
    class="overflow-hidden rounded-lg shadow"
    style="height: 400px;"
    x-data="sessionMap({{ json_encode($markers) }})"
    x-init="initMap()"
    wire:ignore>
</div>

@once
    @push('scripts')
        @vite('resources/js/session-map.js')
    @endpush
@endonce
