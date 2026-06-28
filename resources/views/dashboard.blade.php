@extends('layouts.app')

@section('title', 'داشبورد')

@section('content')

    <h2 class="text-xl font-bold text-gray-800 mb-6">داشبورد</h2>

    <!-- کارت‌های آمار -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500 mb-1">کل عملکردها</p>
            <p class="text-3xl font-bold text-blue-900">{{ $total_performances }}</p>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500 mb-1">در انتظار بررسی</p>
            <p class="text-3xl font-bold text-yellow-500">{{ $pending }}</p>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500 mb-1">تایید شده</p>
            <p class="text-3xl font-bold text-green-600">{{ $approved }}</p>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500 mb-1">رد شده</p>
            <p class="text-3xl font-bold text-red-500">{{ $rejected }}</p>
        </div>

    </div>

    <!-- آخرین آپلودها -->
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold text-gray-700 mb-4">آخرین فایل‌های آپلودشده</h3>

        @if($recent_uploads->isEmpty())
            <p class="text-sm text-gray-400">هنوز فایلی آپلود نشده است.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                <tr class="text-right text-gray-500 border-b">
                    <th class="pb-2 font-medium">نام فایل</th>
                    <th class="pb-2 font-medium">شعبه</th>
                    <th class="pb-2 font-medium">وضعیت</th>
                    <th class="pb-2 font-medium">تاریخ</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @foreach($recent_uploads as $upload)
                    <tr class="py-2">
                        <td class="py-2">{{ $upload->original_filename }}</td>
                        <td class="py-2">{{ $upload->branch?->name ?? '-' }}</td>
                        <td class="py-2">
                            @php
                                $colors = [
                                    'pending'    => 'bg-yellow-100 text-yellow-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'completed'  => 'bg-green-100 text-green-700',
                                    'failed'     => 'bg-red-100 text-red-700',
                                ];
                                $labels = [
                                    'pending'    => 'در انتظار',
                                    'processing' => 'در حال پردازش',
                                    'completed'  => 'تکمیل شده',
                                    'failed'     => 'ناموفق',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs {{ $colors[$upload->status] }}">
                                    {{ $labels[$upload->status] }}
                                </span>
                        </td>
                        <td class="py-2 text-gray-400">{{ $upload->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
