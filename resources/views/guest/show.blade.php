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
        .company-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .company-title {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
        }
        .phone-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            color: #1f2937;
            text-decoration: none;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .phone-pill:hover { background: #f3f4f6; border-color: #d1d5db; }
        .phone-icon {
            width: 14px; height: 14px; display: inline-block;
        }
        .phone-text { white-space: nowrap; }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <div class="company-header">
            <h2 class="company-title">{{ $details['company_name'] ?? 'Savvy Vessel' }}</h2>

            @php($companyPhone = $details['company_phone'] ?? null)
            @if(!empty($companyPhone))
                <a class="phone-pill" href="tel:{{ preg_replace('/\D+/', '', $companyPhone) }}" aria-label="Call {{ $details['company_name'] ?? 'company' }} at {{ $companyPhone }}">
                    <svg class="phone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2.5 5.5c0-1.1.9-2 2-2h2.1c.9 0 1.7.6 1.9 1.4l.7 2.6a2 2 0 0 1-.6 2.1l-1 1a14.5 14.5 0 0 0 6.3 6.3l1-1a2 2 0 0 1 2.1-.6l2.6.7c.8.2 1.4 1 1.4 1.9V19.5c0 1.1-.9 2-2 2h-.5A16.5 16.5 0 0 1 2.5 6v-.5Z" fill="#374151"/>
                    </svg>
                    <span class="phone-text">{{ $companyPhone }}</span>
                </a>
            @endif
        </div>

        <div class="row"><span class="label">Call #:</span> <code>{{ $details['call_number'] ?? '—' }}</code></div>
        <div class="row"><span class="label">Status:</span> <code>{{ $details['call_status'] ?? '—' }}</code></div>
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
        let userMarker = null;
        let destMarker = null;

        // Destination details from server
        const callDetails = {!! json_encode($details) !!};

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

        // --- Destination Pin helpers ---
        function createSvgFromString(svgString) {
            const tpl = document.createElement('template');
            tpl.innerHTML = (svgString || '').trim();
            return tpl.content.firstChild;
        }
        function getMarinaSvg() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 50 50" aria-hidden="true" focusable="false" style="display:block;color:#ffffff"><path fill="currentColor" d="M25.117 10.335c-1.475 0-2.691-1.222-2.691-2.72c0-1.508 1.216-2.719 2.691-2.719a2.703 2.703 0 0 1 2.705 2.719a2.71 2.71 0 0 1-2.705 2.72m21.836 23.806l-1.441-9.027c-.164-.844-.898-1.486-1.771-1.486c-.531 0-1.004.226-1.347.595l-6.639 6.26a2.2 2.2 0 0 0-.307.333a1.957 1.957 0 0 0 .404 2.708c.162.117.342.201.519.273l1.663.559C36.432 38.915 31.818 43.039 27 44V14c2.361-1.01 4.695-3.652 4.695-6.385C31.695 3.957 28.753 1 25.117 1c-3.623 0-6.576 2.957-6.576 6.615c0 2.733 2.108 5.375 4.458 6.385v30c-4.924-.891-9.406-5-11.048-9.645l1.678-.559c.177-.072.353-.156.519-.273c.849-.642 1.038-1.854.402-2.708a1.6 1.6 0 0 0-.308-.333l-6.638-6.26a1.8 1.8 0 0 0-1.335-.595c-.885 0-1.63.642-1.783 1.486L3.045 34.14a2 2 0 0 0-.045.453a1.933 1.933 0 0 0 2.492 1.851l1.559-.51C9.531 43.512 16.626 49 25 49c8.373 0 15.471-5.488 17.951-13.078l1.56.521c.175.06.367.085.565.085a1.93 1.93 0 0 0 1.925-1.936a2 2 0 0 0-.048-.451"/></svg>`;
        }
        function getFuelSvg() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="display:block;color:#ffffff"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 22h12M4 9h10m0 13V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v18m10-9h2a2 2 0 0 1 2 2v2a2 2 0 0 0 2 2a2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L18 5"/></svg>`;
        }
        function getBoatRampSvg() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 50 50" aria-hidden="true" focusable="false" style="display:block;color:#ffffff"><path fill="currentColor" d="m8.5 7.092l9.565 2.639L23.374 6l1.847.455l-5.259 3.742l28.985 8.053l-2.121 7.882l-29.093-8.031C6.462 14.702 8.517 7.092 8.517 7.092m25.44 20.166a6.5 6.5 0 0 0-.375-1.877l13.281 3.697l-.426 1.639zm-12.066-3.332a7 7 0 0 1 1.264-1.398L1 16.408v1.763zm5.486 5.836a2.16 2.16 0 0 0 0-4.322a2.16 2.16 0 1 0 0 4.322M49 43a7.05 7.05 0 0 1-2.943-.648a7.4 7.4 0 0 0-3.047-.672c-1.08 0-2.121.252-3.035.672A7.1 7.1 0 0 1 37.02 43a7.1 7.1 0 0 1-2.955-.648a7.3 7.3 0 0 0-3.035-.672a7.4 7.4 0 0 0-3.045.672a7.1 7.1 0 0 1-2.951.648a7.1 7.1 0 0 1-2.949-.648a7.4 7.4 0 0 0-3.046-.672c-1.08 0-2.12.252-3.035.672a7.1 7.1 0 0 1-2.956.648a7.2 7.2 0 0 1-2.955-.648a7.3 7.3 0 0 0-3.036-.672a7.4 7.4 0 0 0-3.04.672A7.1 7.1 0 0 1 1.068 43L1 25.395l24.227 6.686c-1.67-.807-2.83-2.5-2.83-4.479a4.978 4.978 0 1 1 9.956 0a4.98 4.98 0 0 1-4.977 4.98l-.49-.047l22.078 6.088z"/></svg>`;
        }
        function getAppearanceForDestination(destType) {
            const lower = (destType || '').toLowerCase();
            let glyphEl = null;
            if (lower.includes('marina')) {
                glyphEl = createSvgFromString(getMarinaSvg());
            } else if (lower.includes('fuel')) {
                glyphEl = createSvgFromString(getFuelSvg());
            } else if (lower.includes('boat') && lower.includes('ramp')) {
                glyphEl = createSvgFromString(getBoatRampSvg());
            }
            if (glyphEl) {
                glyphEl.style.width = '16px';
                glyphEl.style.height = '16px';
                glyphEl.style.display = 'block';
                glyphEl.style.color = '#ffffff';
            }
            return { background: '#212121', borderColor: '#000000', glyphEl };
        }
        function createDestinationPinElement(destType) {
            if (!google || !google.maps || !google.maps.marker || !google.maps.marker.PinElement) return null;
            const { background, borderColor, glyphEl } = getAppearanceForDestination(destType);
            const pin = new google.maps.marker.PinElement({ background, borderColor, glyph: glyphEl || undefined });
            return pin.element;
        }

        function ensureMapCentered(position) {
            if (typeof google === 'undefined' || !google.maps) return;
            if (!map) {
                map = new google.maps.Map(mapEl, {
                    center: position,
                    zoom: 15,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                    mapId: "{{ $mapId }}"
                });
            } else {
                map.setCenter(position);
            }
        }

        function setDestinationMarker(lat, lng, name, type) {
            if (typeof google === 'undefined' || !google.maps) return;
            const position = { lat, lng };
            ensureMapCentered(position);

            // Try AdvancedMarker with PinElement first
            if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                const pinEl = createDestinationPinElement(type);
                if (!destMarker) {
                    destMarker = new google.maps.marker.AdvancedMarkerElement({
                        map,
                        position,
                        content: pinEl || undefined,
                        title: name || 'Destination',
                    });
                } else {
                    destMarker.position = position;
                    if (pinEl) destMarker.content = pinEl;
                }
            } else {
                // Fallback to default Marker
                if (!destMarker) {
                    destMarker = new google.maps.Marker({ position, map, title: name || 'Destination' });
                } else {
                    destMarker.setPosition(position);
                }
            }
        }

        function setUserMarker(lat, lng) {
            if (typeof google === 'undefined' || !google.maps) return;
            const position = { lat, lng };
            ensureMapCentered(position);
            if (!userMarker) {
                userMarker = new google.maps.Marker({ position, map, title: 'Your location' });
            } else {
                userMarker.setPosition(position);
            }
        }

        // Resize handling to ensure the map fills the viewport dynamically
        function triggerMapResize() {
            if (map && typeof google !== 'undefined' && google.maps) {
                google.maps.event.trigger(map, 'resize');
            }
        }
        window.addEventListener('resize', triggerMapResize);

        // Destination extracted from callDetails
        const destination = (function() {
            if (!callDetails) return null;
            const lat = parseFloat(callDetails.destination_lat);
            const lng = parseFloat(callDetails.destination_long);
            if (isFinite(lat) && isFinite(lng)) {
                return {
                    lat,
                    lng,
                    name: callDetails.destination_name || 'Destination',
                    type: callDetails.destination_type || null
                };
            }
            return null;
        })();
        const hasDestination = !!(destination && typeof destination.lat === 'number' && typeof destination.lng === 'number');

        // If destination exists, render its marker as a separate pin
        if (hasDestination) {
            const tryInit = () => {
                if (typeof google !== 'undefined' && google.maps) {
                    setDestinationMarker(destination.lat, destination.lng, destination.name, destination.type);
                    triggerMapResize();
                } else {
                    setTimeout(tryInit, 100);
                }
            };
            tryInit();
        }

        // Always attempt geolocation to show user's current location and drive the coords box
        if ('geolocation' in navigator) {
            const options = { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 };
            navigator.geolocation.getCurrentPosition(function(pos) {
                const { latitude, longitude, accuracy } = pos.coords;
                updateCoordsBox(latitude, longitude, accuracy);
                setUserMarker(latitude, longitude);
                // If both markers exist, fit bounds to show both
                if (hasDestination && map && typeof google !== 'undefined' && google.maps) {
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend({ lat: destination.lat, lng: destination.lng });
                    bounds.extend({ lat: latitude, lng: longitude });
                    map.fitBounds(bounds);
                } else {
                    triggerMapResize();
                }
            }, function(err) {
                showError(err.message || 'Unable to retrieve your location');
            }, options);

            navigator.geolocation.watchPosition(function(pos) {
                const { latitude, longitude, accuracy } = pos.coords;
                updateCoordsBox(latitude, longitude, accuracy);
                setUserMarker(latitude, longitude);
            });
        } else {
            if (!hasDestination) {
                showError('Geolocation is not supported by your browser');
            }
        }
    })();
</script>

@if(!empty($mapsUrl) && !empty($mapsKey))
    <script async defer src="{{ $mapsUrl }}js?key={{ $mapsKey }}&libraries=marker&map_ids={{ $mapId }}"></script>
@endif

</body>
</html>
