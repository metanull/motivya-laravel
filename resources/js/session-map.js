import * as maplibregl from 'maplibre-gl';
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url';
import 'maplibre-gl/dist/maplibre-gl.css';

// MapLibre v6 locates its worker relative to its own module URL, which no
// longer points at node_modules once Vite bundles it. Use the emitted asset.
maplibregl.setWorkerUrl(maplibreWorkerUrl);

/**
 * Return true when a value is a finite number (not NaN, not Infinity).
 * Accepts both number primitives and numeric strings emitted by PHP's
 * decimal:7 model cast so that either form passes validation.
 *
 * @param {*} v
 * @returns {boolean}
 */
function isFiniteNumber(v) {
    return (typeof v === 'number' || typeof v === 'string') && v !== '' && isFinite(Number(v));
}

/**
 * Return the URL when it is http(s) or relative (resolved against the current
 * page), otherwise null. Blocks javascript:, data: and similar schemes.
 *
 * @param {*} url
 * @returns {string|null}
 */
function safeHref(url) {
    if (typeof url !== 'string' || url.trim() === '') return null;
    try {
        const parsed = new URL(url, window.location.href);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:' ? parsed.href : null;
    } catch {
        return null;
    }
}

/**
 * Build the marker popup as DOM nodes. Session titles and coach names are
 * user-supplied, so every value is set through textContent, never as HTML.
 *
 * @param {Object} props          - GeoJSON feature properties of the clicked marker
 * @param {string} priceFormatted - Price already formatted for display
 * @returns {HTMLElement}
 */
function buildPopupContent(props, priceFormatted) {
    const text = (v) => (v === null || v === undefined ? '' : String(v));

    const container = document.createElement('div');
    container.style.minWidth = '180px';
    container.style.padding = '4px';

    const title = document.createElement('strong');
    title.textContent = text(props.title);
    container.append(title, document.createElement('br'));

    const details = document.createElement('span');
    details.style.fontSize = '0.85em';
    details.style.color = '#555';
    details.append(
        `${text(props.coach)} — ${text(props.date)} ${text(props.time)}`,
        document.createElement('br'),
        `€${priceFormatted}`,
    );
    container.append(details);

    const href = safeHref(props.url);
    if (href !== null) {
        const link = document.createElement('a');
        link.href = href;
        link.style.color = '#4f46e5';
        link.style.fontSize = '0.85em';
        link.textContent = 'View session →';
        container.append(document.createElement('br'), link);
    }

    return container;
}

/**
 * Alpine.js data factory for the session map component.
 *
 * Uses MapLibre GL JS (open-source, no API key required) with free
 * OpenFreeMap vector tiles. Marker clustering is handled by MapLibre's
 * built-in GeoJSON cluster support.
 *
 * IMPORTANT: The MapLibre Map and Popup objects are stored in closure-local
 * variables (_map, _popup) rather than on the Alpine reactive state object.
 * MapLibre uses non-configurable, non-writable internal properties (such as
 * colour arrays) that violate the ES2015 Proxy invariant when Alpine wraps them
 * in a reactive proxy, producing "'rgb' is a read-only and non-configurable
 * data property" errors that corrupt the Alpine/Livewire update cycle and
 * prevent the booking confirmation modal from opening.
 *
 * @param {string} containerId    - The DOM element ID to render the map into
 * @param {Array}  markers        - Array of session marker objects from SessionQueryService::mapMarkers()
 * @param {Array}  fallbackCenter - [lng, lat] to use when no markers are present (default: Brussels)
 * @param {string} styleUrl       - MapLibre style URL (resolved server-side by MapRenderConfigService)
 */
export function sessionMap(
    containerId,
    markers,
    fallbackCenter = [4.3517, 50.8503],
    styleUrl = 'https://tiles.openfreemap.org/styles/liberty',
) {
    // Closure-local variables — intentionally NOT on the Alpine reactive object
    // so that MapLibre's internal state is never wrapped in a Proxy.
    let _map = null;
    let _popup = null;

    // Validate fallbackCenter: both elements must be finite numbers.
    const validFallback =
        Array.isArray(fallbackCenter) &&
        fallbackCenter.length >= 2 &&
        isFiniteNumber(fallbackCenter[0]) &&
        isFiniteNumber(fallbackCenter[1])
            ? [Number(fallbackCenter[0]), Number(fallbackCenter[1])]
            : [4.3517, 50.8503];

    return {
        initMap() {
            // Idempotent init: destroy existing instance before recreating.
            // Required for Livewire wire:navigate SPA-style navigation which
            // re-mounts Alpine components without a full page reload.
            if (_map !== null) {
                _map.remove();
                _map = null;
                _popup = null;
            }

            _map = new maplibregl.Map({
                container: containerId,
                style: styleUrl,
                center: validFallback,
                zoom: 11,
            });

            _map.addControl(new maplibregl.NavigationControl(), 'top-right');

            _popup = new maplibregl.Popup({ closeButton: true, closeOnClick: false });

            _map.on('load', () => {
                this.addMarkers(markers);
            });

            // Listen for Livewire marker-sync events (fired from Index.php render
            // when filters change while wire:ignore prevents DOM re-render).
            window.addEventListener('map-markers-updated', (event) => {
                if (event.detail && Array.isArray(event.detail.markers)) {
                    this.addMarkers(event.detail.markers);
                }
            });
        },

        addMarkers(markerList) {
            if (!_map) return;

            // Reject markers where lat or lng is missing, null, or non-numeric.
            // PHP's decimal:7 cast returns numeric strings; coerce to Number here.
            const validMarkers = markerList.filter(
                (s) => isFiniteNumber(s.latitude) && isFiniteNumber(s.longitude),
            );

            const geojson = {
                type: 'FeatureCollection',
                features: validMarkers.map((session) => ({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: [Number(session.longitude), Number(session.latitude)],
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

            if (_map.getSource('sessions')) {
                _map.getSource('sessions').setData(geojson);
            } else {
                _map.addSource('sessions', {
                    type: 'geojson',
                    data: geojson,
                    cluster: true,
                    clusterMaxZoom: 14,
                    clusterRadius: 50,
                });

                // Cluster circles
                _map.addLayer({
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
                _map.addLayer({
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
                _map.addLayer({
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
                _map.on('click', 'clusters', (e) => {
                    const features = _map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                    const clusterId = features[0].properties.cluster_id;
                    // MapLibre v4+ returns a Promise; the old callback argument is ignored.
                    _map.getSource('sessions')
                        .getClusterExpansionZoom(clusterId)
                        .then((zoom) => {
                            _map.easeTo({ center: features[0].geometry.coordinates, zoom });
                        })
                        .catch(() => {});
                });

                // Click individual marker → popup
                _map.on('click', 'unclustered-point', (e) => {
                    const props = e.features[0].properties;
                    const coords = e.features[0].geometry.coordinates.slice();
                    const priceFormatted = (props.price / 100).toFixed(2).replace('.', ',');

                    _popup
                        .setLngLat(coords)
                        .setDOMContent(buildPopupContent(props, priceFormatted))
                        .addTo(_map);
                });

                _map.on('mouseenter', 'clusters', () => {
                    _map.getCanvas().style.cursor = 'pointer';
                });
                _map.on('mouseleave', 'clusters', () => {
                    _map.getCanvas().style.cursor = '';
                });
                _map.on('mouseenter', 'unclustered-point', () => {
                    _map.getCanvas().style.cursor = 'pointer';
                });
                _map.on('mouseleave', 'unclustered-point', () => {
                    _map.getCanvas().style.cursor = '';
                });
            }

            if (validMarkers.length > 0) {
                const lngs = validMarkers.map((s) => Number(s.longitude));
                const lats = validMarkers.map((s) => Number(s.latitude));
                _map.fitBounds(
                    [[Math.min(...lngs), Math.min(...lats)], [Math.max(...lngs), Math.max(...lats)]],
                    { padding: 40, maxZoom: 14 },
                );
            }
        },
    };
}

// Register as a global function so inline Alpine x-data can find it
window.sessionMap = sessionMap;
