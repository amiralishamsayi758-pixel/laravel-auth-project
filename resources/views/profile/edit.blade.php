@extends('layouts.app')

@section('title', 'مدیریت پروفایل')

@section('content')
<main class="min-h-screen bg-[linear-gradient(145deg,#f8fafc_0%,#edf7f5_52%,#e7eef2_100%)] px-4 py-16 transition-colors duration-500 dark:bg-[linear-gradient(145deg,#11161d_0%,#18242a_52%,#0d2021_100%)] sm:px-8">
    <x-theme-toggle />
    <div class="mx-auto w-full max-w-4xl">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <x-brand-logo />
            <a href="{{ route('dashboard') }}" class="rounded-md border border-teal-600/25 bg-white/50 px-4 py-2.5 text-sm font-bold text-brand hover:bg-white/80 dark:border-teal-300/20 dark:bg-white/[.04] dark:text-teal-200">بازگشت به داشبورد</a>
        </div>

        <header class="mb-8">
            <p class="mb-2 text-sm font-semibold text-brand dark:text-teal-300">ناحیه کاربری</p>
            <h1 class="text-3xl font-extrabold">مدیریت پروفایل</h1>
            <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">اطلاعات حساب، تصویر، رمز عبور و وضعیت حساب خود را از اینجا مدیریت کنید.</p>
        </header>

        <div class="grid gap-6">
            <section class="rounded-lg border border-white/75 bg-white/70 p-6 shadow-lg backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-8">
                <h2 class="text-xl font-extrabold">اطلاعات پروفایل</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">تغییر جیمیل نیازمند تأیید مجدد آن است.</p>
                @if (session('status') === 'profile-updated')
                    <div class="mt-4 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm text-teal-800 dark:text-teal-200" role="status">اطلاعات پروفایل ذخیره شد.</div>
                @endif
                <div class="mt-4 rounded-md border border-slate-200/70 bg-white/40 p-4 text-sm dark:border-white/10 dark:bg-white/[.04]">وضعیت جیمیل: <strong>{{ $user->hasVerifiedEmail() ? 'تأیید شده' : 'تأیید نشده' }}</strong></div>
                <form action="{{ route('profile.update') }}" method="post" class="mt-6 grid gap-5 sm:grid-cols-2">
                    @csrf
                    @method('patch')
                    <div><label class="field-label" for="profile-username">نام کاربری</label><input @class(['field-input', 'is-invalid' => $errors->profileUpdate->has('username')]) id="profile-username" name="username" type="text" autocomplete="username" value="{{ old('username', $user->username) }}">@error('username', 'profileUpdate')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div><label class="field-label" for="profile-phone">شماره موبایل</label><input @class(['field-input', 'is-invalid' => $errors->profileUpdate->has('phone')]) id="profile-phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone', $user->phone) }}">@error('phone', 'profileUpdate')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="field-label" for="profile-gmail">آدرس جیمیل</label><input @class(['field-input', 'is-invalid' => $errors->profileUpdate->has('gmail')]) id="profile-gmail" name="gmail" type="email" autocomplete="email" value="{{ old('gmail', $user->gmail) }}">@error('gmail', 'profileUpdate')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><button class="primary-button" type="submit">ذخیره اطلاعات</button></div>
                </form>
            </section>

            <section class="rounded-lg border border-white/75 bg-white/70 p-6 shadow-lg backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-8">
                <h2 class="text-xl font-extrabold">تصویر پروفایل</h2>
                @if (session('status') === 'avatar-updated' || session('status') === 'avatar-removed')
                    <div class="mt-4 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm text-teal-800 dark:text-teal-200" role="status">تصویر پروفایل به‌روز شد.</div>
                @endif
                <div class="mt-5 flex flex-wrap items-center gap-5">
                    @if ($user->avatar_path)
                        <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="تصویر پروفایل {{ $user->username }}" class="size-24 rounded-full object-cover ring-4 ring-teal-500/15">
                    @else
                        <div class="flex size-24 items-center justify-center rounded-full bg-teal-100 text-3xl font-extrabold text-brand ring-4 ring-teal-500/15 dark:bg-teal-500/10 dark:text-teal-200" aria-label="تصویر پیش‌فرض">{{ strtoupper(substr($user->username, 0, 1)) }}</div>
                    @endif
                    <p class="text-sm leading-7 text-slate-500 dark:text-slate-400">JPG، JPEG، PNG یا WEBP تا حداکثر ۲ مگابایت.</p>
                </div>
                <form action="{{ route('profile.avatar.store') }}" method="post" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div><label class="field-label" for="avatar">انتخاب تصویر</label><input @class(['field-input', 'is-invalid' => $errors->avatarUpdate->has('avatar')]) id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">@error('avatar', 'avatarUpdate')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <button class="primary-button" type="submit">بارگذاری تصویر</button>
                </form>
                @if ($user->avatar_path)
                    <form action="{{ route('profile.avatar.destroy') }}" method="post" class="mt-3">@csrf @method('delete')<button type="submit" class="w-full rounded-md border border-rose-500/25 px-5 py-3 font-bold text-rose-700 dark:text-rose-200">حذف تصویر</button></form>
                @endif
            </section>

            <section class="rounded-lg border border-white/75 bg-white/70 p-6 shadow-lg backdrop-blur-xl dark:border-white/[.08] dark:bg-[#111b20]/85 sm:p-8">
                <h2 class="text-xl font-extrabold">تغییر رمز عبور</h2>
                @if (session('status') === 'password-updated')<div class="mt-4 rounded-md border border-teal-500/25 bg-teal-50/80 px-4 py-3 text-sm text-teal-800 dark:text-teal-200" role="status">رمز عبور تغییر کرد.</div>@endif
                <form action="{{ route('profile.password.update') }}" method="post" class="mt-6 grid gap-5 sm:grid-cols-2">
                    @csrf @method('put')
                    <div class="sm:col-span-2"><label class="field-label" for="current_password">رمز عبور فعلی</label><input @class(['field-input', 'is-invalid' => $errors->passwordUpdate->has('current_password')]) id="current_password" name="current_password" type="password" autocomplete="current-password">@error('current_password', 'passwordUpdate')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div><label class="field-label" for="new-password">رمز عبور جدید</label><input @class(['field-input', 'is-invalid' => $errors->passwordUpdate->has('password')]) id="new-password" name="password" type="password" autocomplete="new-password">@error('password', 'passwordUpdate')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div><label class="field-label" for="new-password-confirmation">تکرار رمز عبور</label><input class="field-input" id="new-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
                    <div class="sm:col-span-2"><button class="primary-button" type="submit">ذخیره رمز جدید</button></div>
                </form>
            </section>

            <section class="rounded-lg border border-rose-300/50 bg-rose-50/75 p-6 shadow-lg backdrop-blur-xl dark:border-rose-400/20 dark:bg-rose-500/[.07] sm:p-8">
                <h2 class="text-xl font-extrabold text-rose-800 dark:text-rose-200">حذف حساب</h2>
                <p class="mt-2 text-sm leading-7 text-rose-700 dark:text-rose-300">این عملیات بازگشت‌پذیر نیست. برای تأیید، رمز عبور خود را وارد کنید.</p>
                <form action="{{ route('profile.destroy') }}" method="post" class="mt-5 space-y-4">
                    @csrf @method('delete')
                    <div><label class="field-label" for="delete-password">رمز عبور</label><input @class(['field-input', 'is-invalid' => $errors->accountDeletion->has('password')]) id="delete-password" name="password" type="password" autocomplete="current-password">@error('password', 'accountDeletion')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <button type="submit" class="w-full rounded-md bg-rose-700 px-5 py-4 font-bold text-white transition hover:bg-rose-800">حذف دائمی حساب</button>
                </form>
            </section>
        </div>
    </div>
</main>
@endsection
