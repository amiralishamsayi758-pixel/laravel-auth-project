@extends('layouts.app')

@section('title', 'تأیید ایمیل')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'امنیت حساب', 'heading' => 'جیمیل خود را تأیید کنید', 'description' => 'تأیید جیمیل به ما کمک می‌کند از امنیت و درستی حساب شما مطمئن شویم.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-10 xl:px-14">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header class="mb-8">
                    <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">تأیید آدرس جیمیل</p>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">لینک تأیید ارسال شد</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">لینک ارسال‌شده به <span class="font-semibold text-slate-700 dark:text-slate-200">{{ auth()->user()->gmail }}</span> را باز کنید. پوشهٔ spam را نیز بررسی کنید.</p>
                </header>

                @if (session('status') === 'verification-link-sent')
                    <div class="mb-5 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm leading-7 text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/[.08] dark:text-teal-200" role="status">لینک تأیید جدید ارسال شد.</div>
                @endif

                <form action="{{ route('verification.send') }}" method="post">
                    @csrf
                    <button class="primary-button" type="submit">ارسال مجدد لینک تأیید</button>
                </form>

                <form action="{{ route('logout') }}" method="post" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full rounded-md border border-slate-300/80 bg-white/50 px-5 py-3.5 font-bold text-slate-700 transition hover:bg-slate-100 dark:border-white/15 dark:bg-white/[.04] dark:text-slate-200 dark:hover:bg-white/[.08]">خروج از حساب</button>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
