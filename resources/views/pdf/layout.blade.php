<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'JosVeggies' }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #14231a; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #1d4421; }
        .muted { color: #6b7a70; }
        .confidential { color: #c8401b; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { text-align: left; background: #f1e7cf; padding: 6px 8px; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e6e2d6; vertical-align: top; }
        .right { text-align: right; }
        .box { display: inline-block; width: 10px; height: 10px; border: 1px solid #14231a; margin-right: 6px; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 9px; color: #6b7a70; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="footer">JosVeggies · generated {{ now()->format('j M Y H:i') }} WAT · Powered and developed by {{ config('josveggies.brand.powered_by') }}</div>
    @yield('content')
</body>
</html>
