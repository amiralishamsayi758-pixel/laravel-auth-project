@extends('layouts.app')

@section('title', 'دسترسی غیرمجاز')

@section('content')
<main class="flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)]">
    <x-theme-toggle />
    <section class="w-full max-w-lg rounded-lg border border-white/75 bg-white/70 p-8 text-center shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85">
        <p class="text-sm font-semibold text-rose-700 dark:text-rose-300">403</p>
        <h1 class="mt-2 text-2xl font-extrabold">دسترسی به این بخش مجاز نیست</h1>
        <p class="mt-4 text-sm leading-7 text-slate-500 dark:text-slate-400">حساب شما مجوز لازم برای مشاهدهٔ این صفحه را ندارد.</p>
        <a href="{{ route('dashboard') }}" class="primary-button mt-7">بازگشت به داشبورد</a>
    </section>
</main>
@endsection
