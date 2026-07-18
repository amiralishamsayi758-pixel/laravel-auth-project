<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'هم‌مسیر')">
    <title>@yield('title', 'هم‌مسیر')</title>
    <script>
        (() => {
            try {
                const stored = localStorage.getItem('theme');
                const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            } catch (error) {
                document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
            }
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
