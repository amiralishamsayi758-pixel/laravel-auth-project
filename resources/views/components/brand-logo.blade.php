@props(['light' => false])

<a href="{{ url('/') }}" {{ $attributes->class(['inline-flex w-fit items-center gap-2 text-xl font-extrabold', 'text-white' => $light, 'text-ink dark:text-slate-100' => ! $light]) }} aria-label="صفحه اصلی هم‌مسیر">
    <svg class="h-9 w-9" viewBox="0 0 48 48" fill="none" aria-hidden="true">
        <circle cx="24" cy="24" r="21" fill="currentColor" opacity=".08"/>
        <path d="M13 25c0-6.1 4.9-11 11-11s11 4.9 11 11" stroke="#14b8a6" stroke-width="4" stroke-linecap="round"/>
        <path d="M16 32c2.4-2.4 5-3.6 8-3.6s5.6 1.2 8 3.6" stroke="#e76f51" stroke-width="4" stroke-linecap="round"/>
    </svg>
    <span>هم<span class="text-teal-400">‌مسیر</span></span>
</a>
