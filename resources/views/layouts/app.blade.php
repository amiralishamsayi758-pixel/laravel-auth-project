<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'هم‌مسیر')">
    <title>@yield('title', 'هم‌مسیر')</title>
    <script>
        (() => {
            const root = document.documentElement;
            const storageKey = 'theme';
            let savedTheme = null;

            try {
                const stored = localStorage.getItem(storageKey);
                savedTheme = stored === 'light' || stored === 'dark' ? stored : null;
            } catch (error) {
                // Storage is optional; system preference remains available.
            }

            const systemDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
            const dark = savedTheme ? savedTheme === 'dark' : systemDark;

            root.classList.toggle('dark', dark);
            root.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-100 font-sans text-ink antialiased transition-colors duration-500 dark:bg-slate-950 dark:text-slate-100">
    @yield('content')
</body>
</html>
