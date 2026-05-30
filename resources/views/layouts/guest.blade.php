<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — ভেন্ডর রেজিস্ট্রেশন</title>

    {{-- Bootstrap 5.3 --}}
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

    {{-- Google Fonts: Hind Siliguri (Bengali support) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    @livewireStyles

    <style>
        body {
            font-family: 'Hind Siliguri', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #FFF8F5 0%, #F0FDF9 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>

    {{ $slot }}

    {{-- Bootstrap 5.3 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmIGOJ96Oc7GUxMqr5kblMUTIRL"
        crossorigin="anonymous"></script>

    @livewireScripts
</body>
</html>