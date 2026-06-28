@extends('layouts.app')

@section('title', 'گزارش‌ها')

@section('content')

    <h2 class="text-xl font-bold text-gray-800 mb-6">گزارش عملکرد</h2>

    <!-- فیلترها -->
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-2 md:grid-cols-4 gap-4">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">از تاریخ</label>
                <input type="text" name="from" value="{{ request('from') }}" placeholder="14050101"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">تا تاریخ</label>
                <input type="text" name="to" value="{{ request('to') }}" placeholder="14050130"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">شعبه</label>
                <select name="branch_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">همه شعب</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->code }}" {{ request('branch_code') == $branch->code ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="w-full bg-blue-900 text-white rounded-lg px-5 py-2 text-sm hover:bg-blue-800 transition">
                    نمایش گزارش
                </button>
            </div>

        </form>
    </div>

    <!-- جدول گزارش -->
    @if($data !== null)
        <div class="bg-white rounded-xl shadow p-5">

            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700">
                    نتایج — {{ count($data) }} همکار
                </h3>
                <a href="{{ route('exports.performances') }}?from={{ request('from') }}&to={{ request('to') }}&branch_code={{ request('branch_code') }}"
                   class="bg-green-600 text-white rounded-lg px-4 py-1.5 text-sm hover:bg-green-700 transition">
                    دانلود Excel
                </a>
            </div>

            @if(empty($data))
                <p class="text-sm text-gray-400">رکوردی یافت نشد.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="text-right text-gray-500 border-b">
                            <th class="pb-2 font-medium">ردیف</th>
                            <th class="pb-2 font-medium">کد پرسنلی</th>
                            <th class="pb-2 font-medium">نام همکار</th>
                            <th class="pb-2 font-medium">شعبه</th>
                            @foreach($serviceTypes as $type)
                                <th class="pb-2 font-medium">{{ $type->name }}</th>
                            @endforeach
                            <th class="pb-2 font-medium">جمع کل</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($data as $index => $row)
                            <tr class="{{ $index % 2 === 0 ? 'bg-gray-50' : '' }}">
                                <td class="py-2 text-gray-400">{{ $index + 1 }}</td>
                                <td class="py-2">{{ $row['personnel_code'] }}</td>
                                <td class="py-2 font-medium">{{ $row['full_name'] }}</td>
                                <td class="py-2">{{ $row['branch'] }}</td>
                                @foreach($serviceTypes as $type)
                                    <td class="py-2 text-center">
                                        {{ $row['services'][$type->name] ?? 0 }}
                                    </td>
                                @endforeach
                                <td class="py-2 text-center font-bold text-blue-900">
                                    {{ $row['total'] }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400 text-sm">
            برای نمایش گزارش، بازه تاریخ را انتخاب کنید.
        </div>
    @endif

@endsection
