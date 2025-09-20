<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CAD Page</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; margin: 2rem; }
        .card { border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem 1.25rem; max-width: 40rem; }
        .title { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem; }
        .row { margin: 0.25rem 0; }
        .label { color: #6b7280; margin-right: 0.25rem; }
        code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">CAD Parameters</div>
        <div class="row"><span class="label">Tenant:</span> <code>{{ $tenant }}</code></div>
        <div class="row"><span class="label">ID:</span> <code>{{ $id }}</code></div>
        <div class="row"><span class="label">Token:</span> <code>{{ $token }}</code></div>
    </div>
</body>
</html>
