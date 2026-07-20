@extends('layouts.app')

@section('title', 'حساب کاربری')

@section('content')
<main class="relative flex min-h-screen items-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-5 py-20 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-12 xl:px-16">
    <x-theme-toggle />

    <div class="mx-auto grid w-full max-w-[1400px] grid-cols-1 items-stretch gap-10 md:grid-cols-[minmax(0,45fr)_minmax(0,55fr)] lg:min-h-[min(720px,calc(100vh-10rem))] xl:grid-cols-[minmax(420px,460px)_minmax(0,1fr)] xl:gap-12">
        <section class="relative order-1 min-h-[360px] overflow-hidden rounded-2xl shadow-[0_28px_80px_-34px_rgba(15,23,42,.55)] md:order-2 md:min-h-[600px]" aria-label="فضای کاری مدرن">
            <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1800&q=88" alt="فضای کاری روشن و مدرن" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-slate-950/70"></div>

            <div class="relative flex h-full min-h-[360px] flex-col justify-between p-8 sm:p-10 md:min-h-[600px] lg:p-12 xl:p-14">
                <x-brand-logo light />

                <div class="max-w-2xl self-end pb-2 text-right text-white sm:pb-4">
                    <p class="mb-4 inline-flex border-r-2 border-coral pr-3 text-sm font-semibold text-teal-100">ورود امن تکمیل شد</p>
                    <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl xl:text-5xl">حساب شما آماده استفاده است</h2>
                    <p class="mt-5 max-w-xl text-sm leading-8 text-slate-200 sm:text-base">این صفحه نمایی یکپارچه از اطلاعات حساب و مسیرهای مدیریت پروفایل شما ارائه می‌دهد.</p>
                </div>
            </div>
        </section>

        <section class="order-2 flex h-full items-center md:order-1">
            <div class="w-full rounded-2xl border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-8 lg:p-9">
                @if (session('status') === 'email-verified')
                    <div class="mb-6 rounded-lg border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm leading-7 text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/[.08] dark:text-teal-200" role="status">جیمیل شما با موفقیت تأیید شد.</div>
                @endif

                <header class="mb-7">
                    <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ناحیه کاربری</p>
                    <h1 class="text-2xl font-extrabold leading-tight sm:text-3xl">خوش آمدید، {{ $user->username }}</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">اطلاعات این حساب از پایگاه داده خوانده شده است.</p>
                </header>

                <dl class="grid auto-rows-fr grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="flex min-h-24 flex-col justify-center rounded-lg border border-teal-500/15 bg-teal-50/55 p-4 dark:border-teal-300/10 dark:bg-teal-300/[.05] sm:col-span-2">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">Gmail</dt>
                        <dd class="mt-2 break-all font-bold text-brand dark:text-teal-300">{{ $user->gmail }}</dd>
                    </div>
                    <div class="flex min-h-24 flex-col justify-center rounded-lg border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">Phone</dt>
                        <dd class="mt-2 font-bold" dir="ltr">{{ $user->phone }}</dd>
                    </div>
                    <div class="flex min-h-24 flex-col justify-center rounded-lg border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">Username</dt>
                        <dd class="mt-2 break-all font-bold">{{ $user->username }}</dd>
                    </div>
                    <div class="flex min-h-24 flex-col justify-center rounded-lg border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">وضعیت جیمیل</dt>
                        <dd class="mt-2 font-bold">{{ $user->gmail_verified_at ? 'تأیید شده' : 'تأیید نشده' }}</dd>
                    </div>
                    <div class="flex min-h-24 flex-col justify-center rounded-lg border border-slate-200/70 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[.04]">
                        <dt class="text-xs text-slate-500 dark:text-slate-400">زمان ایجاد حساب</dt>
                        <dd class="mt-2 font-bold" dir="ltr">{{ $user->created_at?->format('Y-m-d H:i') ?? 'نامشخص' }}</dd>
                    </div>
                </dl>

                <div class="mt-7 grid gap-3">
                    @can('access-admin')
                        <a href="{{ route('admin.dashboard') }}" class="flex min-h-12 w-full items-center justify-center rounded-lg border border-amber-500/30 bg-amber-50/70 px-5 py-3 font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-300/20 dark:bg-amber-500/[.08] dark:text-amber-200 dark:hover:bg-amber-500/[.14]">ناحیه مدیریت</a>
                    @endcan
                    <a href="{{ route('profile.edit') }}" class="flex min-h-12 w-full items-center justify-center rounded-lg border border-teal-600/25 bg-teal-50/60 px-5 py-3 font-bold text-brand transition hover:bg-teal-100 dark:border-teal-300/20 dark:bg-teal-500/[.07] dark:text-teal-200 dark:hover:bg-teal-500/[.12]">مدیریت پروفایل</a>
                    <form action="{{ route('logout') }}" method="post" class="w-full">
                        @csrf
                        <button type="submit" class="min-h-12 w-full rounded-lg border border-rose-500/25 bg-rose-50/60 px-5 py-3 font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/20 dark:bg-rose-500/[.07] dark:text-rose-200 dark:hover:bg-rose-500/[.12]">خروج امن</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
