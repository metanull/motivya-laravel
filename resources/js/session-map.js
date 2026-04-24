import { Loader } from '@googlemaps/js-api-loader';
import { MarkerClusterer } from '@googlemaps/markerclusterer';

/**
 * Alpine.js data factory for the session map component.
 *
 * Receives a JSON-encoded markers array from the Blade component and the
 * Google Maps API key exposed via VITE_GOOGLE_MAPS_API_KEY.
 *
 * @param {Array} markers - Array of session marker objects from SessionQueryService::mapMarkers()
 */
export function sessionMap(markers) {
    return {
        map: null,
        clusterer: null,

        async initMap() {
            const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY ?? '';

            const loader = new Loader({
                apiKey,
                version: 'weekly',
                libraries: ['maps', 'marker'],
            });

            const { Map } = await loader.importLibrary('maps');
            const { AdvancedMarkerElement } = await loader.importLibrary('marker');

            // Centre on Brussels by default
            this.map = new Map(document.getElementById('session-map'), {
                center: { lat: 50.8503, lng: 4.3517 },
                zoom: 11,
                mapId: 'session-map',
            });

            this.addMarkers(markers, AdvancedMarkerElement);
        },

        addMarkers(markers, AdvancedMarkerElement) {
            if (!this.map) return;

            if (this.clusterer) {
                this.clusterer.clearMarkers();
            }

            const mapMarkers = markers.map((session) => {
                const priceFormatted = (session.price / 100).toFixed(2).replace('.', ',');

                const infoContent = document.createElement('div');
                infoContent.style.cssText = 'min-width:180px;padding:4px;';
                infoContent.innerHTML = `
                    <strong>${session.title}</strong><br>
                    <span style="font-size:0.85em;color:#555;">
                        ${session.coach} — ${session.date} ${session.time}<br>
                        €${priceFormatted}
                    </span><br>
                    <a href="${session.url}" style="color:#4f46e5;font-size:0.85em;">
                        View session →
                    </a>`;

                const infoWindow = new google.maps.InfoWindow({
                    content: infoContent,
                });

                const marker = new AdvancedMarkerElement({
                    map: this.map,
                    position: { lat: session.latitude, lng: session.longitude },
                    title: session.title,
                });

                marker.addListener('click', () => {
                    infoWindow.open({ anchor: marker, map: this.map });
                });

                return marker;
            });

            this.clusterer = new MarkerClusterer({ map: this.map, markers: mapMarkers });

            if (markers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                markers.forEach((s) => bounds.extend({ lat: s.latitude, lng: s.longitude }));
                this.map.fitBounds(bounds, 40);
            }
        },
    };
}

// Register as a global function so inline Alpine x-data can find it
window.sessionMap = sessionMap;
