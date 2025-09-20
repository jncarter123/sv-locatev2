<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CAD Page</title>
    <style>
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            overflow: hidden; /* prevent scrolling */
        }
        .page {
            display: flex;
            flex-direction: column;
            height: 100vh; /* full viewport height */
        }
        .card {
            flex: 0 0 auto;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            background: #fff;
        }
        .title { font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .row { margin: 0.125rem 0; font-size: 0.875rem; }
        .label { color: #6b7280; margin-right: 0.25rem; }
        code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 0.25rem; }

        /* Map fills remaining space */
        #map {
            flex: 1 1 auto;
            width: 100%;
            min-height: 0; /* allows flex child to shrink properly */
            border-top: 1px solid #e5e7eb;
        }

        .coords-box {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: 12px;
            background: #111827;
            color: #f9fafb;
            font-size: 0.875rem;
            padding: 0.5rem 0.5rem 0.5rem 0.75rem;
            border-radius: 0.375rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            opacity: 0.9;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .coords-box.error { background: #7f1d1d; }
        .coords-text { white-space: nowrap; }
        .copy-btn {
            appearance: none;
            border: 1px solid #374151;
            background: #1f2937;
            color: #f9fafb;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        .copy-btn:disabled { opacity: 0.6; cursor: default; }
        .copy-success {
            color: #34d399; /* emerald-400 */
            font-size: 0.75rem;
        }
        .coords-box.error { background: #7f1d1d; }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <div class="title">{{ $details->company_name ?? 'Savvy Vessel' }}</div>
    </div>

    <div id="map" role="region" aria-label="User location map"></div>
</div>

<div id="coords" class="coords-box">
    <span id="coords-text" class="coords-text">Locating…</span>
    <button id="copy-btn" class="copy-btn" type="button" aria-label="Copy coordinates" disabled>Copy</button>
    <span id="copy-msg" class="copy-success" aria-live="polite" style="display:none;">Copied!</span>
</div>

<script>
    (function() {
        const coordsBox = document.getElementById('coords');
        const coordsTextEl = document.getElementById('coords-text');
        const copyBtn = document.getElementById('copy-btn');
        const copyMsg = document.getElementById('copy-msg');
        const mapEl = document.getElementById('map');
        let lastLat = null, lastLng = null;
        let map = null;
        let marker = null;

        function setCopyEnabled(enabled) {
            copyBtn.disabled = !enabled;
        }

        async function copyToClipboard(text) {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    // Fallback for older browsers
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                copyMsg.style.display = 'inline';
                setTimeout(() => (copyMsg.style.display = 'none'), 1200);
            } catch (e) {
                copyBtn.textContent = 'Copy failed';
                setTimeout(() => (copyBtn.textContent = 'Copy'), 1200);
            }
        }

        copyBtn.addEventListener('click', () => {
            if (lastLat == null || lastLng == null) return;
            const text = `${lastLat.toFixed(6)},${lastLng.toFixed(6)}`;
            copyToClipboard(text);
        });

        function updateCoordsBox(lat, lng, accuracy) {
            lastLat = lat;
            lastLng = lng;
            coordsTextEl.textContent = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}${accuracy ? ` (±${Math.round(accuracy)}m)` : ''}`;
            coordsBox.classList.remove('error');
            setCopyEnabled(true);
        }

        function showError(msg) {
            coordsTextEl.textContent = msg;
            coordsBox.classList.add('error');
            setCopyEnabled(false);
        }

        function ensureMap(lat, lng) {
            if (typeof google === 'undefined' || !google.maps) return;
            const position = { lat, lng };
            if (!map) {
                map = new google.maps.Map(mapEl, {
                    center: position,
                    zoom: 15,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                });
            }
            if (!marker) {
                marker = new google.maps.Marker({ position, map, title: 'Your location' });
            } else {
                marker.setPosition(position);
            }
            map.setCenter(position);
        }

        // Resize handling to ensure the map fills the viewport dynamically
        function triggerMapResize() {
            if (map && typeof google !== 'undefined' && google.maps) {
                google.maps.event.trigger(map, 'resize');
            }
        }
        window.addEventListener('resize', triggerMapResize);

        if ('geolocation' in navigator) {
            const options = { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 };
            navigator.geolocation.getCurrentPosition(function(pos) {
                const { latitude, longitude, accuracy } = pos.coords;
                updateCoordsBox(latitude, longitude, accuracy);
                ensureMap(latitude, longitude);
                triggerMapResize();
            }, function(err) {
                showError(err.message || 'Unable to retrieve your location');
            }, options);

            navigator.geolocation.watchPosition(function(pos) {
                const { latitude, longitude, accuracy } = pos.coords;
                updateCoordsBox(latitude, longitude, accuracy);
                ensureMap(latitude, longitude);
            });
        } else {
            showError('Geolocation is not supported by your browser');
        }
    })();
</script>

@if(!empty($mapsUrl) && !empty($mapsKey))
    <script async defer src="{{ $mapsUrl }}js?key={{ $mapsKey }}"></script>
@endif

</body>
</html>
