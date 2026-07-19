@extends('layouts.app')

@section('title', 'ورود به حساب کاربری')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'ورود امن', 'heading' => 'دوباره به هم‌مسیر خوش آمدید', 'description' => 'با جیمیل یا نام کاربری خود وارد شوید.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-10 xl:px-14">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header class="mb-8">
                    <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ورود به حساب</p>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">اطلاعات ورود را وارد کنید</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">از جیمیل یا نام کاربری خود استفاده کنید.</p>
                </header>
                @if (session('status'))
                    <div class="mb-5 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm leading-7 text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/[.08] dark:text-teal-200" role="status">{{ session('status') }}</div>
                @endif
                <form action="{{ route('login.store') }}" method="post" class="space-y-5">
                    @csrf
                    <div>
                        <label class="field-label" for="login">جیمیل یا نام کاربری</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('login')]) id="login" name="login" type="text" autocomplete="username" value="{{ old('login') }}" aria-describedby="login-error" @if (! $errors->any() || $errors->has('login')) autofocus @endif @error('login') aria-invalid="true" @enderror>
                        @error('login')<p id="login-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="password">رمز عبور</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('password')]) id="password" name="password" type="password" autocomplete="current-password" aria-describedby="password-error" @if (! $errors->has('login') && $errors->has('password')) autofocus @endif @error('password') aria-invalid="true" @enderror>
                        @error('password')<p id="password-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                        <a class="mt-3 inline-block text-sm font-semibold text-brand hover:underline dark:text-teal-300" href="{{ route('password.request') }}">رمز عبور را فراموش کرده‌اید؟</a>
                    </div>
                    <button class="primary-button" type="submit">ورود</button>
                </form>
                <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">حساب ندارید؟ <a class="font-bold text-brand hover:underline dark:text-teal-300" href="{{ route('register.create') }}">ایجاد حساب</a></p>
            </div>
        </div>
    </section>
</main>
@endsection
