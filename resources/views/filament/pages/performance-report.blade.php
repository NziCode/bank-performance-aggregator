<x-filament-panels::page>

    <form wire:submit="generate">
        {{ $this->form }}
        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit">
                نمایش گزارش
            </x-filament::button>
            @if($reportData)
                <x-filament::button wire:click="exportExcel" color="success">
                    دانلود Excel
                </x-filament::button>
            @endif
        </div>
    </form>

    @if($reportData !== null)
        <div class="mt-6 bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-3 py-2 text-right">ردیف</th>
                    <th class="px-3 py-2 text-right">کد پرسنلی</th>
                    <th class="px-3 py-2 text-right">نام همکار</th>
                    <th class="px-3 py-2 text-right">شعبه</th>
                    @foreach($serviceTypes as $type)
                        <th class="px-3 py-2 text-center">{{ $type }}</th>
                    @endforeach
                    <th class="px-3 py-2 text-center font-bold">جمع کل</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @foreach($reportData as $index => $row)
                    <tr class="{{ $index % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}">
                        <td class="px-3 py-2 text-gray-400">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">{{ $row['personnel_code'] }}</td>
                        <td class="px-3 py-2 font-medium">{{ $row['full_name'] }}</td>
                        <td class="px-3 py-2">{{ $row['branch'] }}</td>
                        @foreach($serviceTypes as $type)
                            <td class="px-3 py-2 text-center">{{ $row['services'][$type] ?? 0 }}</td>
                        @endforeach
                        <td class="px-3 py-2 text-center font-bold text-blue-900">{{ $row['total'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="mt-6 bg-white rounded-xl shadow p-10 text-center text-gray-400 text-sm">
            برای نمایش گزارش، بازه تاریخ را وارد کنید و دکمه نمایش گزارش را بزنید.
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
