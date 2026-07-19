@extends('layouts.app')

@section('title', 'ناحیه مدیریت')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'دسترسی مدیر', 'heading' => 'زیرساخت امن مدیریت', 'description' => 'این صفحه فقط دسترسی مبتنی بر role را نشان می‌دهد. مدیریت کاربران در Phase 11 اضافه خواهد شد.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-12">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ناحیه مدیریت</p>
                <h1 class="text-2xl font-extrabold sm:text-3xl">خوش آمدید، {{ $admin->username }}</h1>
                <p class="mt-4 text-sm leading-7 text-slate-500 dark:text-slate-400">دسترسی administrator شما با Gate لاراول تأیید شده است. این Phase هیچ اطلاعات حساس یا فهرست کاربران را نمایش نمی‌دهد.</p>
                <a href="{{ route('dashboard') }}" class="primary-button mt-7">بازگشت به داشبورد</a>
            </div>
        </div>
    </section>
</main>
@endsection
