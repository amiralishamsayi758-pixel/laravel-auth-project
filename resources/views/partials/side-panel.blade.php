<section class="relative hidden min-h-screen overflow-hidden lg:block" aria-label="فضای کاری مدرن">
    <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1600&q=85" alt="فضای کاری روشن و مدرن" class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0 bg-slate-950/60"></div>
    <div class="relative flex h-full max-w-xl flex-col justify-between p-12 xl:p-16">
        <x-brand-logo light />
        <div class="pb-10 text-white">
            <p class="mb-4 inline-flex border-r-2 border-coral pr-3 text-sm font-semibold text-teal-100">{{ $eyebrow }}</p>
            <h1 class="max-w-lg text-4xl font-extrabold leading-tight xl:text-5xl">{{ $heading }}</h1>
            <p class="mt-5 max-w-md text-base leading-8 text-slate-200">{{ $description }}</p>
        </div>
    </div>
</section>
