import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

/**
 * Alpine.js data factory for the session map component.
 *
 * Uses MapLibre GL JS (open-source, no API key required) with free
 * OpenFreeMap vector tiles. Marker clustering is handled by MapLibre's
 * built-in GeoJSON cluster support.
 *
 * @param {string} containerId - The DOM element ID to render the map into
 * @param {Array} markers - Array of session marker objects from SessionQueryService::mapMarkers()
 * @param {Array} fallbackCenter - [lng, lat] to use when no markers are present (default: Brussels)
 */
export function sessionMap(containerId, markers, fallbackCenter = [4.3517, 50.8503]) {
    return {
        map: null,
        popup: null,

        initMap() {
            this.map = new maplibregl.Map({
                container: containerId,
                // OpenFreeMap liberty style — free, no API key, hosted by the OSM community
                style: 'https://tiles.openfreemap.org/styles/liberty',
                center: fallbackCenter,
                zoom: 11,
            });

            this.map.addControl(new maplibregl.NavigationControl(), 'top-right');

            this.popup = new maplibregl.Popup({ closeButton: true, closeOnClick: false });

            this.map.on('load', () => {
                this.addMarkers(markers);
            });
        },

        addMarkers(markers) {
            if (!this.map) return;

            const geojson = {
                type: 'FeatureCollection',
                features: markers.map((session) => ({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: [session.longitude, session.latitude],
                    },
                    properties: {
                        id: session.id,
                        title: session.title,
                        coach: session.coach,
                        date: session.date,
                        time: session.time,
                        price: session.price,
                        url: session.url,
                    },
                })),
            };

            if (this.map.getSource('sessions')) {
                this.map.getSource('sessions').setData(geojson);
            } else {
                this.map.addSource('sessions', {
                    type: 'geojson',
                    data: geojson,
                    cluster: true,
                    clusterMaxZoom: 14,
                    clusterRadius: 50,
                });

                // Cluster circles
                this.map.addLayer({
                    id: 'clusters',
                    type: 'circle',
                    source: 'sessions',
                    filter: ['has', 'point_count'],
                    paint: {
                        'circle-color': '#4f46e5',
                        'circle-radius': ['step', ['get', 'point_count'], 16, 10, 22, 30, 28],
                        'circle-opacity': 0.85,
                    },
                });

                // Cluster count labels
                this.map.addLayer({
                    id: 'cluster-count',
                    type: 'symbol',
                    source: 'sessions',
                    filter: ['has', 'point_count'],
                    layout: {
                        'text-field': '{point_count_abbreviated}',
                        'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                        'text-size': 12,
                    },
                    paint: { 'text-color': '#ffffff' },
                });

                // Individual session markers
                this.map.addLayer({
                    id: 'unclustered-point',
                    type: 'circle',
                    source: 'sessions',
                    filter: ['!', ['has', 'point_count']],
                    paint: {
                        'circle-color': '#4f46e5',
                        'circle-radius': 8,
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff',
                    },
                });

                // Click cluster → zoom in
                this.map.on('click', 'clusters', (e) => {
                    const features = this.map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                    const clusterId = features[0].properties.cluster_id;
                    this.map.getSource('sessions').getClusterExpansionZoom(clusterId, (err, zoom) => {
                        if (err) return;
                        this.map.easeTo({ center: features[0].geometry.coordinates, zoom });
                    });
                });

                // Click individual marker → popup
                this.map.on('click', 'unclustered-point', (e) => {
                    const props = e.features[0].properties;
                    const coords = e.features[0].geometry.coordinates.slice();
                    const priceFormatted = (props.price / 100).toFixed(2).replace('.', ',');

                    this.popup
                        .setLngLat(coords)
                        .setHTML(`
                            <div style="min-width:180px;padding:4px;">
                                <strong>${props.title}</strong><br>
                                <span style="font-size:0.85em;color:#555;">
                                    ${props.coach} — ${props.date} ${props.time}<br>
                                    €${priceFormatted}
                                </span><br>
                                <a href="${props.url}" style="color:#4f46e5;font-size:0.85em;">
                                    View session →
                                </a>
                            </div>`)
                        .addTo(this.map);
                });

                this.map.on('mouseenter', 'clusters', () => {
                    this.map.getCanvas().style.cursor = 'pointer';
                });
                this.map.on('mouseleave', 'clusters', () => {
                    this.map.getCanvas().style.cursor = '';
                });
                this.map.on('mouseenter', 'unclustered-point', () => {
                    this.map.getCanvas().style.cursor = 'pointer';
                });
                this.map.on('mouseleave', 'unclustered-point', () => {
                    this.map.getCanvas().style.cursor = '';
                });
            }

            if (markers.length > 0) {
                const lngs = markers.map((s) => s.longitude);
                const lats = markers.map((s) => s.latitude);
                this.map.fitBounds(
                    [[Math.min(...lngs), Math.min(...lats)], [Math.max(...lngs), Math.max(...lats)]],
                    { padding: 40, maxZoom: 14 },
                );
            }
        },
    };
}

// Register as a global function so inline Alpine x-data can find it
window.sessionMap = sessionMap;
