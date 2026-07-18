@extends('layouts.app')

@section('title', 'ایجاد حساب کاربری')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'شروعی ساده و مطمئن', 'heading' => 'حساب شما، نقطه شروع ارتباط‌های بهتر', 'description' => 'اطلاعات پایه را وارد کنید تا حساب کاربری شما برای مراحل بعدی آماده شود.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-10 xl:px-14">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header class="mb-8">
                    <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ایجاد حساب</p>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">اطلاعات خود را وارد کنید</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">تمام فیلدها برای نسخه نهایی در نظر گرفته شده‌اند.</p>
                </header>
                <form action="#" method="post" class="space-y-5">
                    @csrf
                    <div><label class="field-label" for="gmail">آدرس جیمیل</label><input class="field-input" id="gmail" name="gmail" type="email" autocomplete="email" placeholder="example@gmail.com"><p class="field-help">آدرس باید به @gmail.com ختم شود.</p></div>
                    <div><label class="field-label" for="phone">شماره موبایل</label><input class="field-input" id="phone" name="phone" type="tel" autocomplete="tel" inputmode="numeric" maxlength="11" placeholder="09123456789"><p class="field-help">شماره موبایل را با 09 وارد کنید.</p></div>
                    <div><label class="field-label" for="username">نام کاربری</label><input class="field-input" id="username" name="username" type="text" autocomplete="username" placeholder="نام کاربری"><p class="field-help">نامی که در حساب شما نمایش داده می‌شود.</p></div>
                    <button class="primary-button" type="submit">ادامه و دریافت کد تأیید</button>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
