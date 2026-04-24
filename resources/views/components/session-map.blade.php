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
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLaA=" crossorigin=""></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

        <script>
            function sessionMap(markers) {
                return {
                    map: null,
                    clusterGroup: null,

                    initMap() {
                        // Centre on Brussels by default
                        this.map = L.map('session-map').setView([50.8503, 4.3517], 11);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                            maxZoom: 19,
                        }).addTo(this.map);

                        this.clusterGroup = L.markerClusterGroup();

                        this.addMarkers(markers);

                        this.map.addLayer(this.clusterGroup);
                    },

                    addMarkers(markers) {
                        if (!this.clusterGroup) return;
                        this.clusterGroup.clearLayers();

                        markers.forEach((session) => {
                            const priceFormatted = (session.price / 100).toFixed(2).replace('.', ',');
                            const popup = `
                                <div style="min-width:180px;">
                                    <strong>${session.title}</strong><br>
                                    <span style="font-size:0.85em;color:#555;">
                                        ${session.coach} &mdash; ${session.date} ${session.time}<br>
                                        &euro;${priceFormatted}
                                    </span><br>
                                    <a href="${session.url}" style="color:#4f46e5;font-size:0.85em;">
                                        {{ __('sessions.view_session') }} &rarr;
                                    </a>
                                </div>`;

                            L.marker([session.latitude, session.longitude])
                                .bindPopup(popup)
                                .addTo(this.clusterGroup);
                        });

                        if (markers.length > 0) {
                            this.map.fitBounds(this.clusterGroup.getBounds(), { padding: [30, 30] });
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
