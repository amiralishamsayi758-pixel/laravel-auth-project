@extends('layouts.app')

@section('title', 'حساب کاربری')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'ورود امن تکمیل شد', 'heading' => 'حساب شما آماده استفاده است', 'description' => 'این صفحه در فاز فعلی فقط پیش‌نمایش رابط ناحیه کاربری است.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-12">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header><p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ناحیه کاربری</p><h1 class="text-2xl font-extrabold sm:text-3xl">خوش آمدید</h1><p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">اطلاعات واقعی کاربر در فاز احراز هویت نمایش داده خواهد شد.</p></header>
                <div class="mt-7 grid gap-4 sm:grid-cols-2"><div class="rounded-md border border-teal-500/15 bg-teal-50/55 p-4 dark:border-teal-300/10 dark:bg-teal-300/[.05]"><p class="text-xs text-slate-500">وضعیت حساب</p><p class="mt-2 font-bold text-brand dark:text-teal-300">پیش‌نمایش رابط</p></div><div class="rounded-md border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]"><p class="text-xs text-slate-500">نام کاربری</p><p class="mt-2 font-bold">در فاز آینده</p></div></div>
                <button type="button" disabled class="mt-7 w-full cursor-not-allowed rounded-md border border-rose-500/25 bg-rose-50/60 px-5 py-3.5 font-bold text-rose-700 opacity-60 dark:border-rose-400/20 dark:bg-rose-500/[.07] dark:text-rose-200">خروج امن در فاز آینده</button>
            </div>
        </div>
    </section>
</main>
@endsection
