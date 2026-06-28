@extends('layouts.app')

@section('title', 'آپلود فایل')

@section('content')

    <h2 class="text-xl font-bold text-gray-800 mb-6">آپلود فایل عملکرد</h2>

    <!-- فرم آپلود -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <form method="POST" action="{{ route('uploads.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        دوره گزارش (مثال: 140501)
                    </label>
                    <input
                        type="text"
                        name="period"
                        value="{{ old('period') }}"
                        placeholder="140501"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    فایل‌های Excel (می‌توانید چند فایل انتخاب کنید)
                </label>
                <input
                    type="file"
                    name="files[]"
                    multiple
                    accept=".xlsx"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                @error('files.*')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="bg-blue-900 text-white rounded-lg px-6 py-2 text-sm font-medium hover:bg-blue-800 transition"
            >
                آپلود و پردازش
            </button>
        </form>
    </div>

    <!-- لیست آپلودها -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-700 mb-4">تاریخچه آپلودها</h3>

        @if($uploads->isEmpty())
            <p class="text-sm text-gray-400">هنوز فایلی آپلود نشده است.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                <tr class="text-right text-gray-500 border-b">
                    <th class="pb-2 font-medium">نام فایل</th>
                    <th class="pb-2 font-medium">شعبه</th>
                    <th class="pb-2 font-medium">دوره</th>
                    <th class="pb-2 font-medium">ردیف‌های موفق</th>
                    <th class="pb-2 font-medium">ردیف‌های رد شده</th>
                    <th class="pb-2 font-medium">وضعیت</th>
                    <th class="pb-2 font-medium">تاریخ</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @foreach($uploads as $upload)
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
                    <tr>
                        <td class="py-2">{{ $upload->original_filename }}</td>
                        <td class="py-2">{{ $upload->branch?->name ?? '-' }}</td>
                        <td class="py-2">{{ $upload->period ?? '-' }}</td>
                        <td class="py-2 text-green-600">{{ $upload->rows_processed }}</td>
                        <td class="py-2 text-red-500">{{ $upload->rows_rejected }}</td>
                        <td class="py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $colors[$upload->status] }}">
                                    {{ $labels[$upload->status] }}
                                </span>
                        </td>
                        <td class="py-2 text-gray-400">{{ $upload->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $uploads->links() }}
            </div>
        @endif
    </div>

@endsection
