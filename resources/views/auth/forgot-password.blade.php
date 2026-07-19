@extends('layouts.app')

@section('title', 'بازیابی رمز عبور')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'بازیابی امن', 'heading' => 'دسترسی به حساب خود را بازیابی کنید', 'description' => 'لینک بازیابی فقط برای جیمیل ثبت‌شده ارسال می‌شود.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-10 xl:px-14">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header class="mb-8">
                    <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">فراموشی رمز عبور</p>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">جیمیل خود را وارد کنید</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">در صورت وجود حساب، لینک امن تغییر رمز عبور ارسال می‌شود.</p>
                </header>
                @if (session('status'))
                    <div class="mb-5 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm leading-7 text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/[.08] dark:text-teal-200" role="status">{{ session('status') }}</div>
                @endif
                <form action="{{ route('password.email') }}" method="post" class="space-y-5">
                    @csrf
                    <div>
                        <label class="field-label" for="gmail">آدرس جیمیل</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('gmail')]) id="gmail" name="gmail" type="email" autocomplete="email" placeholder="example@gmail.com" value="{{ old('gmail') }}" aria-describedby="gmail-error" autofocus @error('gmail') aria-invalid="true" @enderror>
                        @error('gmail')<p id="gmail-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button class="primary-button" type="submit">ارسال لینک بازیابی</button>
                </form>
                <p class="mt-6 text-center text-sm"><a class="font-bold text-brand hover:underline dark:text-teal-300" href="{{ route('login') }}">بازگشت به ورود</a></p>
            </div>
        </div>
    </section>
</main>
@endsection
