<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Share your contact')</title>
    <link rel="stylesheet" href="{{ asset('css/intake.css') }}">
</head>
<body>
    <button id="toggle-theme">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 8a2.83 2.83 0 0 0 4 4 4 4 0 1 1-4-4"/>
            <path d="M12 2v2"/><path d="M12 20v2"/>
            <path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/>
            <path d="M2 12h2"/><path d="M20 12h2"/>
            <path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/>
        </svg>
    </button>
    <main>
        @yield('content')
    </main>
    <script>
        const btn = document.getElementById('toggle-theme');
        const dark = () => document.body.classList.contains('dark-theme');
        function apply(isDark) {
            document.body.classList.toggle('dark-theme', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        btn.addEventListener('click', () => apply(!dark()));
        const saved = localStorage.getItem('theme');
        if (saved === 'dark' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            apply(true);
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) apply(e.matches);
        });
    </script>
</body>
</html>
