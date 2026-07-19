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
                @if (session('status') === 'email-verified')
                    <div class="mb-5 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm leading-7 text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/[.08] dark:text-teal-200" role="status">جیمیل شما با موفقیت تأیید شد.</div>
                @endif
                <header><p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ناحیه کاربری</p><h1 class="text-2xl font-extrabold sm:text-3xl">خوش آمدید، {{ $user->username }}</h1><p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">اطلاعات این حساب از پایگاه داده خوانده شده است.</p></header>
                <dl class="mt-7 grid gap-4">
                    <div class="rounded-md border border-teal-500/15 bg-teal-50/55 p-4 dark:border-teal-300/10 dark:bg-teal-300/[.05]"><dt class="text-xs text-slate-500 dark:text-slate-400">Gmail</dt><dd class="mt-2 break-all font-bold text-brand dark:text-teal-300">{{ $user->gmail }}</dd></div>
                    <div class="rounded-md border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]"><dt class="text-xs text-slate-500 dark:text-slate-400">Phone</dt><dd class="mt-2 font-bold" dir="ltr">{{ $user->phone }}</dd></div>
                    <div class="rounded-md border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]"><dt class="text-xs text-slate-500 dark:text-slate-400">Username</dt><dd class="mt-2 break-all font-bold">{{ $user->username }}</dd></div>
                    <div class="rounded-md border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]"><dt class="text-xs text-slate-500 dark:text-slate-400">وضعیت جیمیل</dt><dd class="mt-2 font-bold">{{ $user->gmail_verified_at ? 'تأیید شده' : 'تأیید نشده' }}</dd></div>
                    <div class="rounded-md border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]"><dt class="text-xs text-slate-500 dark:text-slate-400">زمان ایجاد حساب</dt><dd class="mt-2 font-bold" dir="ltr">{{ $user->created_at?->format('Y-m-d H:i') ?? 'نامشخص' }}</dd></div>
                </dl>
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}" class="mt-7 flex w-full items-center justify-center rounded-md border border-amber-500/30 bg-amber-50/70 px-5 py-3.5 font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-300/20 dark:bg-amber-500/[.08] dark:text-amber-200 dark:hover:bg-amber-500/[.14]">ناحیه مدیریت</a>
                @endcan
                <a href="{{ route('profile.edit') }}" class="mt-7 flex w-full items-center justify-center rounded-md border border-teal-600/25 bg-teal-50/60 px-5 py-3.5 font-bold text-brand transition hover:bg-teal-100 dark:border-teal-300/20 dark:bg-teal-500/[.07] dark:text-teal-200 dark:hover:bg-teal-500/[.12]">مدیریت پروفایل</a>
                <form action="{{ route('logout') }}" method="post" class="mt-7">
                    @csrf
                    <button type="submit" class="w-full rounded-md border border-rose-500/25 bg-rose-50/60 px-5 py-3.5 font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/20 dark:bg-rose-500/[.07] dark:text-rose-200 dark:hover:bg-rose-500/[.12]">خروج امن</button>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
