@extends('layouts.app')

@section('title', 'عملکردها')

@section('content')

    <h2 class="text-xl font-bold text-gray-800 mb-6">عملکردها</h2>

    <!-- فیلترها -->
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <form method="GET" action="{{ route('performances.index') }}" class="grid grid-cols-2 md:grid-cols-4 gap-4">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">وضعیت</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">همه</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->id }}" {{ request('status') == $status->id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">نوع خدمت</label>
                <select name="service_type_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">همه</option>
                    @foreach($serviceTypes as $type)
                        <option value="{{ $type->id }}" {{ request('service_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

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

            <div class="col-span-2 md:col-span-4 flex gap-2">
                <button type="submit"
                        class="bg-blue-900 text-white rounded-lg px-5 py-2 text-sm hover:bg-blue-800 transition">
                    اعمال فیلتر
                </button>
                <a href="{{ route('performances.index') }}"
                   class="bg-gray-100 text-gray-700 rounded-lg px-5 py-2 text-sm hover:bg-gray-200 transition">
                    پاک کردن
                </a>
            </div>

        </form>
    </div>

    <!-- عملیات دسته‌ای -->
    <form method="POST" action="{{ route('performances.bulk-approve') }}" id="bulk-form">
        @csrf

        <div class="bg-white rounded-xl shadow p-5">

            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700">لیست عملکردها</h3>
                <div class="flex gap-2">
                    <button type="submit" formaction="{{ route('performances.bulk-approve') }}"
                            class="bg-green-600 text-white rounded-lg px-4 py-1.5 text-sm hover:bg-green-700 transition">
                        تایید انتخاب‌شده‌ها
                    </button>
                    <button type="submit" formaction="{{ route('performances.bulk-reject') }}"
                            class="bg-red-500 text-white rounded-lg px-4 py-1.5 text-sm hover:bg-red-600 transition">
                        رد انتخاب‌شده‌ها
                    </button>
                </div>
            </div>

            @if($performances->isEmpty())
                <p class="text-sm text-gray-400">رکوردی یافت نشد.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                    <tr class="text-right text-gray-500 border-b">
                        <th class="pb-2 w-8">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th class="pb-2 font-medium">تاریخ</th>
                        <th class="pb-2 font-medium">همکار</th>
                        <th class="pb-2 font-medium">شعبه</th>
                        <th class="pb-2 font-medium">نوع خدمت</th>
                        <th class="pb-2 font-medium">نام مشتری</th>
                        <th class="pb-2 font-medium">وضعیت</th>
                        <th class="pb-2 font-medium">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($performances as $performance)
                        @php
                            $colors = [
                                1 => 'bg-yellow-100 text-yellow-700',
                                2 => 'bg-green-100 text-green-700',
                                3 => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <tr>
                            <td class="py-2">
                                <input type="checkbox" name="ids[]" value="{{ $performance->id }}">
                            </td>
                            <td class="py-2">{{ $performance->date->format('Y/m/d') }}</td>
                            <td class="py-2">{{ $performance->employee?->full_name }}</td>
                            <td class="py-2">{{ $performance->branch?->name }}</td>
                            <td class="py-2">{{ $performance->serviceType?->name }}</td>
                            <td class="py-2">{{ $performance->customer_name }}</td>
                            <td class="py-2">
                                    <span class="px-2 py-1 rounded-full text-xs {{ $colors[$performance->validation_status_id] }}">
                                        {{ $performance->validationStatus?->name }}
                                    </span>
                            </td>
                            <td class="py-2 flex gap-2">
                                @if($performance->validation_status_id === 1)
                                    <form method="POST" action="{{ route('performances.approve', $performance) }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-green-600 hover:underline text-xs">تایید</button>
                                    </form>
                                    <form method="POST" action="{{ route('performances.reject', $performance) }}">
                                        @csrf
                                        <input type="hidden" name="rejection_reason_id" value="1">
                                        <button type="submit"
                                                class="text-red-500 hover:underline text-xs">رد</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $performances->links() }}
                </div>
            @endif

        </div>
    </form>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
        });
    </script>

@endsection
