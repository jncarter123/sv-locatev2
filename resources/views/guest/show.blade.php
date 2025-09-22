<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $details['company_name'] ?? 'Savvy Vessel' }}</title>
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

            @if(!empty($details['$companyPhone']))
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
    <span id="coords-text" class="coords-text" aria-live="polite">Locating…</span>
    <button id="copy-btn" class="copy-btn" type="button" aria-label="Copy coordinates" disabled>Copy</button>
    <span id="copy-msg" class="copy-success" aria-live="polite" style="display:none;">Copied!</span>
</div>

@vite(['resources/js/app.js'])

<script>
    // Destination details from server
    const callDetails = @json($details);
    const geofences = @json($geofences);

    // Context for guest APIs
    window.guestContext = @json([
        'tenant' => $tenant,
        'guestShareId' => $id,
        'token' => $token
    ]);
</script>

@if(!empty($mapsUrl) && !empty($mapsKey))
    <script async defer src="{{ $mapsUrl }}js?key={{ $mapsKey }}&libraries=marker&map_ids={{ $mapId }}"></script>
@endif

</body>
</html>
