<x-filament-panels::page>
    {{-- Leaflet (open-source map; no API key needed) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div
        x-data="zippiLiveMap({
            center: @js($this->getCenter()),
            feedUrl: @js($this->getFeedUrl()),
            intervalMs: 15000,
        })"
        x-init="init()"
        wire:ignore
    >
        <div class="mb-3 flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                <span x-text="counts.active"></span> active rentals
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                <span x-text="counts.open_alerts"></span> open alerts
            </span>
            <span class="text-gray-400" x-show="lastUpdated">Updated <span x-text="lastUpdated"></span></span>
            <span class="text-danger-500" x-show="error" x-text="error"></span>
        </div>

        <div
            x-ref="map"
            style="height: 70vh; width: 100%; border-radius: 0.75rem; z-index: 0;"
            class="border border-gray-200 dark:border-gray-700"
        ></div>
    </div>

    <script>
        function zippiLiveMap(config) {
            return {
                map: null,
                rentalLayer: null,
                alertLayer: null,
                counts: { active: 0, open_alerts: 0 },
                lastUpdated: null,
                error: null,
                timer: null,

                init() {
                    this.map = L.map(this.$refs.map).setView([config.center.lat, config.center.lng], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19,
                    }).addTo(this.map);

                    this.rentalLayer = L.layerGroup().addTo(this.map);
                    this.alertLayer = L.layerGroup().addTo(this.map);

                    this.refresh();
                    this.timer = setInterval(() => this.refresh(), config.intervalMs);

                    // Stop polling when the Livewire component is torn down.
                    this.$el.addEventListener('livewire:navigating', () => clearInterval(this.timer));
                },

                async refresh() {
                    try {
                        const res = await fetch(config.feedUrl, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Feed error ' + res.status);
                        const data = await res.json();
                        this.draw(data);
                        this.counts = data.counts;
                        this.error = null;
                        this.lastUpdated = new Date().toLocaleTimeString();
                    } catch (e) {
                        this.error = 'Could not refresh positions';
                    }
                },

                draw(data) {
                    this.rentalLayer.clearLayers();
                    this.alertLayer.clearLayers();
                    const bounds = [];

                    (data.rentals || []).forEach(r => {
                        if (r.trail && r.trail.length > 1) {
                            L.polyline(r.trail, { color: '#16a34a', weight: 3, opacity: 0.6 }).addTo(this.rentalLayer);
                        }
                        const marker = L.circleMarker([r.lat, r.lng], {
                            radius: 8, color: '#15803d', fillColor: '#22c55e', fillOpacity: 0.9, weight: 2,
                        }).addTo(this.rentalLayer);
                        marker.bindPopup(
                            `<strong>${r.bike ?? 'Bike'}</strong> (${r.registration_no ?? '—'})<br>` +
                            `Rider: ${r.rider ?? '—'}<br>` +
                            `Booking: ${r.booking_code}<br>` +
                            `Ends: ${r.end_at ?? '—'}<br>` +
                            `Battery: ${r.battery ?? '—'}% · ${r.speed ?? 0} km/h`
                        );
                        bounds.push([r.lat, r.lng]);
                    });

                    const sevColor = { high: '#dc2626', medium: '#f59e0b', low: '#9ca3af' };
                    (data.alerts || []).forEach(a => {
                        L.circleMarker([a.lat, a.lng], {
                            radius: 10, color: sevColor[a.severity] ?? '#9ca3af',
                            fillColor: sevColor[a.severity] ?? '#9ca3af', fillOpacity: 0.5, weight: 2,
                        }).addTo(this.alertLayer)
                          .bindPopup(`<strong>Geofence alert</strong><br>${a.bike ?? 'Bike'} · ${a.severity}`);
                        bounds.push([a.lat, a.lng]);
                    });

                    if (bounds.length && !this._fitted) {
                        this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                        this._fitted = true;
                    }
                },
            };
        }
    </script>
</x-filament-panels::page>
