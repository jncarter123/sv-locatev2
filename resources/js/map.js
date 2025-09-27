(function() {
    const coordsBox = document.getElementById('coords');
    const coordsTextEl = document.getElementById('coords-text');
    const copyBtn = document.getElementById('copy-btn');
    const copyMsg = document.getElementById('copy-msg');
    const mapEl = document.getElementById('map');

    let shareBtn = null;
    let shareMsg = null;
    let pendingLocation = null; // Store location data when waiting for share

    let lastLat = null, lastLng = null;
    let map = null;
    let userMarker = null;
    let destMarker = null;
    let userHasInteracted = false;
    let infoWindow = null;
    let destClickListener = null;
    let geofenceOverlays = [];
    let geofencesInitialized = false;


    // Initialize share button if needed
    function initShareButton() {
        if (shareBtn) return; // Already initialized

        // Check if we need location updates but not auto-request
        const needsShareButton = callDetails &&
            callDetails.allow_location_updates &&
            !callDetails.current_location_locked &&
            !callDetails.auto_request_location;

        if (!needsShareButton) return;

        // Create share button
        shareBtn = document.createElement('button');
        shareBtn.id = 'share-btn';
        shareBtn.textContent = 'Share Location';
        shareBtn.style.cssText = `
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            margin-left: 8px;
            cursor: pointer;
            font-size: 14px;
        `;
        shareBtn.disabled = true; // Initially disabled until location is available

        // Create share message
        shareMsg = document.createElement('span');
        shareMsg.id = 'share-msg';
        shareMsg.textContent = 'Location shared!';
        shareMsg.style.cssText = `
            color: #28a745;
            margin-left: 8px;
            font-size: 14px;
            display: none;
        `;

        // Add to coords box
        coordsBox.appendChild(shareBtn);
        coordsBox.appendChild(shareMsg);

        // Add click handler
        shareBtn.addEventListener('click', () => {
            if (pendingLocation) {
                updateGuestLocation(pendingLocation.latitude, pendingLocation.longitude, pendingLocation.accuracy);
                showShareMessage();
                pendingLocation = null; // Clear pending location after sharing
            }
        });
    }

    function showShareMessage() {
        if (shareMsg) {
            shareMsg.style.display = 'inline';
            setTimeout(() => {
                if (shareMsg) shareMsg.style.display = 'none';
            }, 2000);
        }
    }

    function setShareEnabled(enabled) {
        if (shareBtn) {
            shareBtn.disabled = !enabled;
        }
    }

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
        //coordsTextEl.textContent = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
        coordsBox.classList.remove('error');
        setCopyEnabled(true);

        // Handle location sharing logic
        if (callDetails && callDetails.allow_location_updates && !callDetails.current_location_locked) {
            if (callDetails.auto_request_location) {
                // Automatically update location
                updateGuestLocation(lat, lng, accuracy);
            } else {
                // Store location for manual sharing and enable share button
                pendingLocation = { latitude: lat, longitude: lng, accuracy };
                setShareEnabled(true);
            }
        }
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

    // --- Geofences ---
    function initializeGeofences() {
        if (geofencesInitialized) return;
        if (!Array.isArray(geofences) || geofences.length === 0) {
            geofencesInitialized = true; // prevent re-run
            return;
        }
        if (typeof google === 'undefined' || !google.maps || !map) return;

        geofences.forEach((geofence) => {
            const coords = geofence && geofence.coordinates ? geofence.coordinates : null;
            if (!Array.isArray(coords) || coords.length < 3) return;

            const path = coords.map((coord) => ({
                lat: parseFloat(coord.lat),
                lng: parseFloat(coord.lng),
            })).filter(p => isFinite(p.lat) && isFinite(p.lng));
            if (path.length < 3) return;

            // Color by type
            let fillColor = '#FF0000';
            let strokeColor = '#FF0000';
            const type = geofence.type || geofence.geofence_type || '';
            switch (type) {
                case 'Restricted Area':
                    fillColor = '#FF0000'; strokeColor = '#FF0000';
                    break;
                case 'Operational Zone':
                    fillColor = '#0000FF'; strokeColor = '#0000FF';
                    break;
                case 'Boundary':
                    fillColor = '#00FF00'; strokeColor = '#00FF00';
                    break;
                default:
                    fillColor = '#FFA500'; strokeColor = '#FFA500';
            }

            const polygon = new google.maps.Polygon({
                paths: path,
                strokeColor: strokeColor,
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: fillColor,
                fillOpacity: 0,
                map: map,
            });
            // Attach metadata
            polygon.geofenceId = geofence.id;
            polygon.geofenceName = geofence.name;
            polygon.geofenceType = type;
            polygon.geofenceDescription = geofence.description || '';
            polygon.regionId = geofence.region_id;

            // Click to show info
            const ensureInfo = () => { if (!infoWindow) infoWindow = new google.maps.InfoWindow(); };
            polygon.addListener('click', (e) => {
                ensureInfo();
                const content = `
                        <div>
                            <h5 style="margin:0 0 4px 0;">${geofence.name || 'Geofence'}</h5>
                            <p style="margin:0;font-size:12px;line-height:1.35;">
                                ${type ? 'Type: ' + type + '<br>' : ''}
                                ${geofence.region_name ? 'Region: ' + geofence.region_name + '<br>' : ''}
                                ${geofence.description ? 'Description: ' + geofence.description : ''}
                            </p>
                        </div>
                    `;
                infoWindow.setContent(content);
                infoWindow.setPosition(e.latLng);
                infoWindow.open(map);
            });

            // Label at the polygon's bounding box center
            const bounds = new google.maps.LatLngBounds();
            path.forEach(pt => bounds.extend(pt));
            const center = bounds.getCenter();
            const label = new google.maps.Marker({
                position: center,
                map: map,
                label: {
                    text: geofence.name || 'Geofence',
                    color: strokeColor,
                    fontWeight: 'bold',
                },
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 0 },
                optimized: true,
            });

            // Store for potential toggling
            geofenceOverlays.push({ polygon, label });

            // Highlight if requested
            if (window.highlightGeofenceId && geofence.id === window.highlightGeofenceId) {
                const geofenceBounds = new google.maps.LatLngBounds();
                path.forEach(pt => geofenceBounds.extend(pt));
                map.fitBounds(geofenceBounds);
                polygon.setOptions({ strokeWeight: 4, strokeOpacity: 1.0 });
                if (label) {
                    label.setOptions({ label: { text: geofence.name, color: strokeColor, fontWeight: 'bold', fontSize: '14px' } });
                }
            }
        });

        geofencesInitialized = true;
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
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                mapId: "{{ $mapId }}"
            });
            // Ensure a reusable info window exists
            if (!infoWindow) {
                infoWindow = new google.maps.InfoWindow();
            }
            // Mark that the user has taken control; from now on we don't auto-fit
            map.addListener('dragstart', () => { userHasInteracted = true; });
            map.addListener('zoom_changed', () => { userHasInteracted = true; });
            map.addListener('tilt_changed', () => { userHasInteracted = true; });
            map.addListener('heading_changed', () => { userHasInteracted = true; });
            // Initialize geofences once map is ready
            if (!geofencesInitialized) {
                initializeGeofences();
            }
        }
        // Do not re-center automatically after map is created; respect user interactions
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

        // Setup clickable info window for destination marker
        if (!infoWindow) {
            infoWindow = new google.maps.InfoWindow();
        }
        if (destClickListener) {
            google.maps.event.removeListener(destClickListener);
            destClickListener = null;
        }
        // AdvancedMarkerElement uses 'gmp-click' event
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement && destMarker instanceof google.maps.marker.AdvancedMarkerElement) {
            destClickListener = destMarker.addListener('gmp-click', () => {
                infoWindow.setContent(name || 'Destination');
                // New InfoWindow open signature with anchor
                infoWindow.open({ anchor: destMarker, map, shouldFocus: false });
            });
        } else if (destMarker && destMarker.addListener) {
            // Legacy Marker
            destClickListener = destMarker.addListener('click', () => {
                infoWindow.setContent(name || 'Destination');
                infoWindow.open(map, destMarker);
            });
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

    // --- API helpers ---
    async function logToServer(level, message, context) {
        try {
            await fetch('/api/guest/logger', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ level, message, context }),
                credentials: 'same-origin'
            });
        } catch (e) {
            // Swallow client-side logging errors
            console && console.debug && console.debug('Logger failed', e);
        }
    }

    async function updateGuestLocation(latitude, longitude, accuracy) {
        try {
            const ctx = (window.guestContext || {});
            if (!ctx.tenant || !ctx.guestShareId || !ctx.token) {
                return; // Missing context; do not attempt
            }
            const res = await fetch('/api/guest/location/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    tenant: ctx.tenant,
                    guestShareId: ctx.guestShareId,
                    token: ctx.token,
                    latitude,
                    longitude,
                    accuracy,
                    timestamp: new Date().toISOString()
                }),
                credentials: 'same-origin'
            });
            if (!res.ok) {
                const text = await res.text().catch(() => '');
                await logToServer('error', 'Failed to update guest location', {
                    status: res.status,
                    statusText: res.statusText,
                    response: text,
                    latitude,
                    longitude
                });
            }
        } catch (e) {
            await logToServer('error', 'Exception while updating guest location', {
                message: e && e.message,
                name: e && e.name,
                latitude,
                longitude
            });
        }
    }


    // Always attempt geolocation to show user's current location and drive the coords box
    if ('geolocation' in navigator) {
        // Initialize share button before geolocation
        initShareButton();

        const options = { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 };
        navigator.geolocation.getCurrentPosition(function(pos) {
            const { latitude, longitude, accuracy } = pos.coords;
            updateCoordsBox(latitude, longitude, accuracy);
            setUserMarker(latitude, longitude);

            // Location update logic is now handled in updateCoordsBox

            // If both markers exist, fit bounds to show both
            if (hasDestination && map && typeof google !== 'undefined' && google.maps && !userHasInteracted) {
                const bounds = new google.maps.LatLngBounds();
                bounds.extend({ lat: destination.lat, lng: destination.lng });
                bounds.extend({ lat: latitude, lng: longitude });
                map.fitBounds(bounds);
            } else {
                triggerMapResize();
            }
        }, function(error) {
            let errorMessage = error && error.message ? error.message : 'Unable to retrieve your location';
            let errorCodeStr = 'UNKNOWN_ERROR';
            if (error && typeof error.code !== 'undefined') {
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'User denied the request for geolocation';
                        errorCodeStr = 'PERMISSION_DENIED';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information is unavailable';
                        errorCodeStr = 'POSITION_UNAVAILABLE';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'The request to get user location timed out';
                        errorCodeStr = 'TIMEOUT';
                        break;
                    default:
                        errorMessage = error.message || 'An unknown error occurred';
                        errorCodeStr = 'UNKNOWN_ERROR';
                        break;
                }
            }
            // Show user-friendly error only if no destination
            if (!hasDestination) {
                showError(errorMessage);
            }
            // Log geolocation error
            logToServer('error', 'Failed to get user location', {
                errorCode: errorCodeStr,
                errorMessage: errorMessage,
                rawCode: error && error.code,
                rawMessage: error && error.message
            });
        }, options);

        /*
        navigator.geolocation.watchPosition(function(pos) {
            const { latitude, longitude, accuracy } = pos.coords;
            updateCoordsBox(latitude, longitude, accuracy);
            setUserMarker(latitude, longitude);
            // Push updates continuously
            //updateGuestLocation(latitude, longitude, accuracy);
        }, function(error) {
            // For watch errors, just log
            let errorMessage = error && error.message ? error.message : 'WatchPosition error';
            let errorCodeStr = 'UNKNOWN_ERROR';
            if (error && typeof error.code !== 'undefined') {
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'User denied the request for geolocation';
                        errorCodeStr = 'PERMISSION_DENIED';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information is unavailable';
                        errorCodeStr = 'POSITION_UNAVAILABLE';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'The request to get user location timed out';
                        errorCodeStr = 'TIMEOUT';
                        break;
                    default:
                        errorMessage = error.message || 'An unknown error occurred';
                        errorCodeStr = 'UNKNOWN_ERROR';
                        break;
                }
            }
            logToServer('error', 'Geolocation watch error', {
                errorCode: errorCodeStr,
                errorMessage: errorMessage,
                rawCode: error && error.code,
                rawMessage: error && error.message
            });
        }, options);
        */
    } else {
        if (!hasDestination) {
            showError('Geolocation is not supported by your browser');
        }
        logToServer('error', 'Geolocation not supported');
    }



})();

