@extends('layouts.app')

@section('title', 'تغییر رمز عبور')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[minmax(360px,.85fr)_minmax(520px,1.15fr)]">
    @include('partials.side-panel', ['eyebrow' => 'رمز عبور جدید', 'heading' => 'یک رمز عبور قوی انتخاب کنید', 'description' => 'پس از تغییر رمز عبور، با رمز جدید وارد حساب خود شوید.'])
    <section class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8 lg:px-10 xl:px-14">
        <x-theme-toggle />
        <div class="w-full max-w-lg">
            <x-brand-logo class="mb-8 lg:hidden" />
            <div class="w-full rounded-lg border border-white/75 bg-white/70 p-6 shadow-[0_22px_60px_-28px_rgba(15,23,42,.35)] backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-9 lg:p-10">
                <header class="mb-8"><p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">تغییر رمز عبور</p><h1 class="text-2xl font-extrabold sm:text-3xl">اطلاعات زیر را تکمیل کنید</h1></header>
                <form action="{{ route('password.update') }}" method="post" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div>
                        <label class="field-label" for="gmail">آدرس جیمیل</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('gmail')]) id="gmail" name="gmail" type="email" autocomplete="email" value="{{ old('gmail', $gmail) }}" aria-describedby="gmail-error" @if (! $errors->any() || $errors->has('gmail')) autofocus @endif @error('gmail') aria-invalid="true" @enderror>
                        @error('gmail')<p id="gmail-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="password">رمز عبور جدید</label>
                        <input @class(['field-input', 'is-invalid' => $errors->has('password')]) id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-help password-error" @if (! $errors->has('gmail') && $errors->has('password')) autofocus @endif @error('password') aria-invalid="true" @enderror>
                        <p id="password-help" class="field-help">حداقل ۸ نویسه، شامل حروف کوچک و بزرگ انگلیسی و عدد.</p>
                        @error('password')<p id="password-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="password_confirmation">تکرار رمز عبور جدید</label>
                        <input class="field-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                    </div>
                    <button class="primary-button" type="submit">تغییر رمز عبور</button>
                </form>
                <p class="mt-6 text-center text-sm"><a class="font-bold text-brand hover:underline dark:text-teal-300" href="{{ route('login') }}">بازگشت به ورود</a></p>
            </div>
        </div>
    </section>
</main>
@endsection
