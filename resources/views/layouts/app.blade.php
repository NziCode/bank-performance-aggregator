<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | @yield('title')</title>

    <!-- فونت وزیرمتن -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-800 antialiased">

<!-- نوار بالا -->
<nav class="bg-blue-900 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <span class="text-lg font-semibold">سامانه ارزیابی عملکرد</span>
        <div class="flex items-center gap-4 text-sm">
            <span>{{ auth()->user()?->full_name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="hover:underline">خروج</button>
            </form>
        </div>
    </div>
</nav>

<!-- منوی کناری + محتوا -->
<div class="max-w-7xl mx-auto px-4 py-6 flex gap-6">

    <!-- منو -->
    <aside class="w-52 shrink-0">
        <ul class="bg-white rounded-xl shadow p-3 space-y-1 text-sm">
            <li>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-50 {{ request()->routeIs('dashboard') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">
                    داشبورد
                </a>
            </li>
            <li>
                <a href="{{ route('uploads.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-50 {{ request()->routeIs('uploads.*') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">
                    آپلود فایل
                </a>
            </li>
            <li>
                <a href="{{ route('performances.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-50 {{ request()->routeIs('performances.*') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">
                    عملکردها
                </a>
            </li>
            <li>
                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-50 {{ request()->routeIs('reports.*') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">
                    گزارش‌ها
                </a>
            </li>
        </ul>
    </aside>

    <!-- محتوای اصلی -->
    <main class="flex-1">
        @if(session('success'))
            <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

</div>

@livewireScripts
</body>
</html>
