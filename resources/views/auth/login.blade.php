<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">

    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-blue-900">سامانه ارزیابی عملکرد</h1>
        <p class="text-gray-500 text-sm mt-1">با کد پرسنلی وارد شوید</p>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-50 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                کد پرسنلی
            </label>
            <input
                type="text"
                name="personnel_code"
                value="{{ old('personnel_code') }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="کد پرسنلی خود را وارد کنید"
                autofocus
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                رمز عبور
            </label>
            <input
                type="password"
                name="password"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="رمز عبور خود را وارد کنید"
            >
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="remember" id="remember" class="rounded">
            <label for="remember" class="text-sm text-gray-600">مرا به خاطر بسپار</label>
        </div>

        <button
            type="submit"
            class="w-full bg-blue-900 text-white rounded-lg py-2 text-sm font-medium hover:bg-blue-800 transition"
        >
            ورود
        </button>

    </form>

</div>

</body>
</html>
