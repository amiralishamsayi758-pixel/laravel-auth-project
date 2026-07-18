@extends('layouts.app')

@section('title', 'تأیید حساب کاربری')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'یک قدم تا تکمیل حساب', 'heading' => 'تأیید سریع، شروعی مطمئن', 'description' => 'کد شما در نسخه نهایی زمان محدودی خواهد داشت.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-10 xl:px-14">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header class="mb-7"><p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">تأیید حساب</p><h1 class="text-2xl font-extrabold sm:text-3xl">کد تأیید را وارد کنید</h1><p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">کد چهاررقمی ارسال‌شده را وارد کنید.</p></header>
                <div class="mb-6 flex items-center justify-between rounded-md border border-teal-500/15 bg-teal-50/55 px-4 py-3 dark:border-teal-300/10 dark:bg-teal-300/[.05]"><span class="text-sm text-slate-600 dark:text-slate-400">زمان باقی‌مانده</span><span class="font-mono text-xl font-bold text-brand dark:text-teal-300" dir="ltr">--:--</span></div>
                <form action="{{ route('verification.store') }}" method="post" class="space-y-6">
                    @csrf
                    <input data-code-value type="hidden" name="code" value="{{ old('code') }}">
                    <fieldset>
                        <legend class="sr-only">شش رقم کد تأیید</legend>
                        <div data-code-inputs class="flex w-full justify-center gap-1.5 sm:gap-3" dir="ltr">
                            @foreach (range(1, 6) as $digit)
                                <input @class(['code-input', 'is-invalid' => $errors->has('code')]) type="text" maxlength="1" inputmode="numeric" autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}" aria-label="رقم {{ $digit }} کد تأیید" value="{{ substr((string) old('code'), $digit - 1, 1) }}" @error('code') aria-invalid="true" @enderror>
                            @endforeach
                        </div>
                    </fieldset>
                    @error('code')<p class="field-error text-center" role="alert">{{ $message }}</p>@enderror
                    <button class="primary-button" type="submit">تأیید کد</button>
                </form>
                <div class="mt-6 border-t border-slate-200/70 pt-5 dark:border-white/10"><button type="button" disabled class="w-full cursor-not-allowed rounded-md border border-teal-600/25 bg-white/40 px-4 py-3 text-sm font-semibold text-brand opacity-60 dark:border-teal-300/20 dark:bg-white/[.04] dark:text-teal-300">ارسال مجدد کد در فاز آینده</button></div>
            </div>
        </div>
    </section>
</main>
@endsection
