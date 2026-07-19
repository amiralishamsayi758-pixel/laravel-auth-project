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
                @if (session('status') === 'account-deleted')
                    <div class="mb-5 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm leading-7 text-teal-800 dark:border-teal-400/20 dark:bg-teal-500/[.08] dark:text-teal-200" role="status">حساب شما حذف شد.</div>
                @endif
                <header class="mb-8">
                    <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ایجاد حساب</p>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">اطلاعات خود را وارد کنید</h1>
                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">تمام فیلدها برای نسخه نهایی در نظر گرفته شده‌اند.</p>
                </header>
                @error('registration')
                    <div class="mb-5 rounded-md border border-rose-500/35 bg-rose-50/80 px-4 py-3 text-sm leading-7 text-rose-800 dark:border-rose-400/30 dark:bg-rose-500/[.09] dark:text-rose-200" role="alert">{{ $message }}</div>
                @enderror
                <form action="{{ route('register.store') }}" method="post" class="space-y-5">
                    @csrf
                    <div>
                        <label class="field-label" for="gmail">آدرس جیمیل</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('gmail')]) id="gmail" name="gmail" type="email" autocomplete="email" placeholder="example@gmail.com" value="{{ old('gmail') }}" aria-describedby="gmail-help gmail-error" @if (! $errors->any() || $errors->has('gmail')) autofocus @endif @error('gmail') aria-invalid="true" @enderror>
                        <p id="gmail-help" class="field-help">آدرس باید به @gmail.com ختم شود.</p>
                        @error('gmail')<p id="gmail-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="phone">شماره موبایل</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('phone')]) id="phone" name="phone" type="tel" autocomplete="tel" inputmode="numeric" maxlength="11" placeholder="09123456789" value="{{ old('phone') }}" aria-describedby="phone-help phone-error" @if (! $errors->has('gmail') && $errors->has('phone')) autofocus @endif @error('phone') aria-invalid="true" @enderror>
                        <p id="phone-help" class="field-help">شماره موبایل را با 09 وارد کنید.</p>
                        @error('phone')<p id="phone-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="username">نام کاربری</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('username')]) id="username" name="username" type="text" autocomplete="username" placeholder="نام کاربری" value="{{ old('username') }}" aria-describedby="username-help username-error" @if (! $errors->has('gmail') && ! $errors->has('phone') && $errors->has('username')) autofocus @endif @error('username') aria-invalid="true" @enderror>
                        <p id="username-help" class="field-help">نامی که در حساب شما نمایش داده می‌شود.</p>
                        @error('username')<p id="username-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="password">رمز عبور</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('password')]) id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-help password-error" @if (! $errors->has('gmail') && ! $errors->has('phone') && ! $errors->has('username') && $errors->has('password')) autofocus @endif @error('password') aria-invalid="true" @enderror>
                        <p id="password-help" class="field-help">حداقل ۸ نویسه، شامل حروف کوچک و بزرگ انگلیسی و عدد.</p>
                        @error('password')<p id="password-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="password_confirmation">تکرار رمز عبور</label>
                        <input class="field-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" aria-describedby="password-confirmation-help">
                        <p id="password-confirmation-help" class="field-help">رمز عبور را دوباره وارد کنید.</p>
                    </div>
                    <button class="primary-button" type="submit">ادامه و دریافت کد تأیید</button>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
