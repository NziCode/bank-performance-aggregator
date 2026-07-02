<x-filament-panels::page>

    {{-- ─── فرم فیلتر ──────────────────────────────────────────────────────── --}}
    <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:24px;">
        <form wire:submit="generate">
            {{ $this->form }}
            <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                <x-filament::button type="submit" icon="heroicon-o-chart-bar">
                    نمایش گزارش
                </x-filament::button>
                @if($summaryResult || $detailedResult || $breakdownResult)
                    <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                        خروجی Excel
                    </x-filament::button>
                    <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document">
                        خروجی PDF
                    </x-filament::button>
                @endif
            </div>
        </form>
    </div>

    @php
        $hasResult = $summaryResult || $detailedResult || $breakdownResult;
    @endphp

    @if($hasResult)
        {{-- ─── نوار خلاصه سرصفحه ──────────────────────────────────────────── --}}
        @php
            $gt = $summaryResult['grand_total']
                ?? $breakdownResult['grand_total']
                ?? ['all' => count($detailedResult ?? []), 2 => null, 1 => null, 3 => null];
        @endphp
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:16px 20px;margin-bottom:16px;">
            <div style="font-size:17px;font-weight:bold;color:#1e3a5f;margin-bottom:6px;">
                {{ $entityLabel }}
                @if($breakdownResult)
                    — ریزبندی بر اساس {{ $breakdownResult['breakdown_label'] }}
                @endif
            </div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:10px;">
                {{ $periodLabel }} &nbsp;|&nbsp; {{ $fromLabel }} تا {{ $toLabel }}
            </div>
            @if($gt['all'] !== null)
                <div style="display:flex;gap:20px;font-size:13px;flex-wrap:wrap;">
                    <span style="color:#374151;">مجموع کل: <strong>{{ $gt['all'] }}</strong></span>
                    <span style="color:#16a34a;">✓ تایید: <strong>{{ $gt[2] }}</strong></span>
                    <span style="color:#ca8a04;">⏳ انتظار: <strong>{{ $gt[1] }}</strong></span>
                    <span style="color:#dc2626;">✗ رد: <strong>{{ $gt[3] }}</strong></span>
                </div>
            @endif
        </div>
    @endif

    {{-- ─── جدول ریزبندی (breakdown) ─────────────────────────────────────── --}}
    @if($breakdownResult)
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);margin-bottom:16px;overflow:hidden;">
            <div style="padding:10px 20px;border-bottom:1px solid #f3f4f6;font-weight:600;color:#374151;font-size:13px;">
                آمار به تفکیک {{ $breakdownResult['breakdown_label'] }}
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="background:#1e3a5f;color:#fff;">
                            <th rowspan="2" style="padding:10px 14px;text-align:right;white-space:nowrap;vertical-align:middle;border-left:1px solid #2d5a8e;">{{ $breakdownResult['breakdown_label'] }}</th>
                            @foreach($breakdownResult['service_types'] as $type)
                                <th colspan="4" style="padding:6px 12px;text-align:center;white-space:nowrap;border-left:1px solid #2d5a8e;border-bottom:1px solid #2d5a8e;">{{ $type }}</th>
                            @endforeach
                            <th colspan="4" style="padding:6px 12px;text-align:center;white-space:nowrap;border-left:1px solid #2d5a8e;border-bottom:1px solid #2d5a8e;">جمع کل</th>
                        </tr>
                        <tr style="background:#1e3a5f;color:#fff;">
                            @foreach($breakdownResult['service_types'] as $type)
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#bfdbfe;border-left:1px solid #2d5a8e;">کل</th>
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#86efac;">تایید</th>
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fde68a;">انتظار</th>
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fca5a5;border-left:1px solid #2d5a8e;">رد</th>
                            @endforeach
                            <th style="padding:5px 8px;text-align:center;font-size:10px;color:#bfdbfe;border-left:1px solid #2d5a8e;">کل</th>
                            <th style="padding:5px 8px;text-align:center;font-size:10px;color:#86efac;">تایید</th>
                            <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fde68a;">انتظار</th>
                            <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fca5a5;">رد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdownResult['rows'] as $idx => $row)
                            <tr style="background:{{ $idx % 2 === 0 ? '#f9fafb' : '#fff' }};border-bottom:1px solid #f3f4f6;">
                                <td style="padding:9px 14px;font-weight:500;">{{ $row['label'] }}</td>
                                @foreach($breakdownResult['service_types'] as $type)
                                    @php $cell = $row['services'][$type]; @endphp
                                    <td style="padding:9px 8px;text-align:center;font-weight:bold;">{{ $cell['all'] }}</td>
                                    <td style="padding:9px 8px;text-align:center;color:#16a34a;">{{ $cell[2] }}</td>
                                    <td style="padding:9px 8px;text-align:center;color:#ca8a04;">{{ $cell[1] }}</td>
                                    <td style="padding:9px 8px;text-align:center;color:#dc2626;border-left:1px solid #f3f4f6;">{{ $cell[3] }}</td>
                                @endforeach
                                <td style="padding:9px 8px;text-align:center;font-weight:bold;color:#1e3a5f;">{{ $row['grand_total']['all'] }}</td>
                                <td style="padding:9px 8px;text-align:center;color:#16a34a;">{{ $row['grand_total'][2] }}</td>
                                <td style="padding:9px 8px;text-align:center;color:#ca8a04;">{{ $row['grand_total'][1] }}</td>
                                <td style="padding:9px 8px;text-align:center;color:#dc2626;">{{ $row['grand_total'][3] }}</td>
                            </tr>
                        @endforeach
                        {{-- ردیف جمع کل --}}
                        <tr style="background:#eff6ff;font-weight:bold;border-top:2px solid #bfdbfe;">
                            <td style="padding:10px 14px;">جمع کل</td>
                            @foreach($breakdownResult['service_types'] as $type)
                                @php
                                    $total = ['all' => 0, 2 => 0, 1 => 0, 3 => 0];
                                    foreach ($breakdownResult['rows'] as $r) {
                                        $c = $r['services'][$type];
                                        $total['all'] += $c['all']; $total[2] += $c[2]; $total[1] += $c[1]; $total[3] += $c[3];
                                    }
                                @endphp
                                <td style="padding:10px 8px;text-align:center;">{{ $total['all'] }}</td>
                                <td style="padding:10px 8px;text-align:center;color:#16a34a;">{{ $total[2] }}</td>
                                <td style="padding:10px 8px;text-align:center;color:#ca8a04;">{{ $total[1] }}</td>
                                <td style="padding:10px 8px;text-align:center;color:#dc2626;border-left:1px solid #bfdbfe;">{{ $total[3] }}</td>
                            @endforeach
                            <td style="padding:10px 8px;text-align:center;color:#1e3a5f;">{{ $breakdownResult['grand_total']['all'] }}</td>
                            <td style="padding:10px 8px;text-align:center;color:#16a34a;">{{ $breakdownResult['grand_total'][2] }}</td>
                            <td style="padding:10px 8px;text-align:center;color:#ca8a04;">{{ $breakdownResult['grand_total'][1] }}</td>
                            <td style="padding:10px 8px;text-align:center;color:#dc2626;">{{ $breakdownResult['grand_total'][3] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ─── گزارش کلی (summary) ──────────────────────────────────────────── --}}
    @if($summaryResult)

        {{-- جدول نوع خدمت --}}
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);margin-bottom:16px;overflow:hidden;">
            <div style="padding:12px 20px;border-bottom:1px solid #f3f4f6;font-weight:600;color:#374151;font-size:13px;">
                جمع کل به تفکیک نوع خدمت
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#1e3a5f;color:#fff;">
                            <th style="padding:10px 16px;text-align:right;">نوع خدمت</th>
                            <th style="padding:10px 16px;text-align:center;min-width:60px;">کل</th>
                            <th style="padding:10px 16px;text-align:center;min-width:60px;color:#86efac;">تایید</th>
                            <th style="padding:10px 16px;text-align:center;min-width:60px;color:#fde68a;">انتظار</th>
                            <th style="padding:10px 16px;text-align:center;min-width:60px;color:#fca5a5;">رد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryResult['service_types'] as $i => $type)
                            @php $cell = $summaryResult['totals'][$type]; @endphp
                            <tr style="background:{{ $i % 2 === 0 ? '#f9fafb' : '#fff' }};border-bottom:1px solid #f3f4f6;">
                                <td style="padding:10px 16px;font-weight:500;">{{ $type }}</td>
                                <td style="padding:10px 16px;text-align:center;font-weight:bold;">{{ $cell['all'] }}</td>
                                <td style="padding:10px 16px;text-align:center;color:#16a34a;">{{ $cell[2] }}</td>
                                <td style="padding:10px 16px;text-align:center;color:#ca8a04;">{{ $cell[1] }}</td>
                                <td style="padding:10px 16px;text-align:center;color:#dc2626;">{{ $cell[3] }}</td>
                            </tr>
                        @endforeach
                        <tr style="background:#eff6ff;font-weight:bold;border-top:2px solid #bfdbfe;">
                            <td style="padding:10px 16px;">جمع کل</td>
                            <td style="padding:10px 16px;text-align:center;color:#1e3a5f;">{{ $summaryResult['grand_total']['all'] }}</td>
                            <td style="padding:10px 16px;text-align:center;color:#16a34a;">{{ $summaryResult['grand_total'][2] }}</td>
                            <td style="padding:10px 16px;text-align:center;color:#ca8a04;">{{ $summaryResult['grand_total'][1] }}</td>
                            <td style="padding:10px 16px;text-align:center;color:#dc2626;">{{ $summaryResult['grand_total'][3] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- جدول ریز همکاران --}}
        @if(count($summaryResult['by_employee']) > 1)
            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
                <div style="padding:12px 20px;border-bottom:1px solid #f3f4f6;font-weight:600;color:#374151;font-size:13px;">
                    ریز عملکرد به تفکیک همکار
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr style="background:#1e3a5f;color:#fff;">
                                <th rowspan="2" style="padding:10px 12px;text-align:right;white-space:nowrap;vertical-align:middle;border-left:1px solid #2d5a8e;">کد پرسنلی</th>
                                <th rowspan="2" style="padding:10px 12px;text-align:right;white-space:nowrap;vertical-align:middle;border-left:1px solid #2d5a8e;">نام همکار</th>
                                <th rowspan="2" style="padding:10px 12px;text-align:right;white-space:nowrap;vertical-align:middle;border-left:1px solid #2d5a8e;">محل خدمت</th>
                                @foreach($summaryResult['service_types'] as $type)
                                    <th colspan="4" style="padding:6px 12px;text-align:center;white-space:nowrap;border-left:1px solid #2d5a8e;border-bottom:1px solid #2d5a8e;">{{ $type }}</th>
                                @endforeach
                                <th colspan="4" style="padding:6px 12px;text-align:center;white-space:nowrap;border-left:1px solid #2d5a8e;border-bottom:1px solid #2d5a8e;">جمع</th>
                            </tr>
                            <tr style="background:#1e3a5f;color:#fff;">
                                @foreach($summaryResult['service_types'] as $type)
                                    <th style="padding:5px 8px;text-align:center;font-size:10px;color:#bfdbfe;border-left:1px solid #2d5a8e;">کل</th>
                                    <th style="padding:5px 8px;text-align:center;font-size:10px;color:#86efac;">تایید</th>
                                    <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fde68a;">انتظار</th>
                                    <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fca5a5;border-left:1px solid #2d5a8e;">رد</th>
                                @endforeach
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#bfdbfe;border-left:1px solid #2d5a8e;">کل</th>
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#86efac;">تایید</th>
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fde68a;">انتظار</th>
                                <th style="padding:5px 8px;text-align:center;font-size:10px;color:#fca5a5;">رد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summaryResult['by_employee'] as $idx => $row)
                                <tr style="background:{{ $idx % 2 === 0 ? '#f9fafb' : '#fff' }};border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:9px 12px;">{{ $row['personnel_code'] }}</td>
                                    <td style="padding:9px 12px;font-weight:500;">{{ $row['full_name'] }}</td>
                                    <td style="padding:9px 12px;color:#6b7280;font-size:11px;">{{ $row['workplace'] }}</td>
                                    @foreach($summaryResult['service_types'] as $type)
                                        @php $cell = $row['services'][$type]; @endphp
                                        <td style="padding:9px 8px;text-align:center;font-weight:bold;">{{ $cell['all'] }}</td>
                                        <td style="padding:9px 8px;text-align:center;color:#16a34a;">{{ $cell[2] }}</td>
                                        <td style="padding:9px 8px;text-align:center;color:#ca8a04;">{{ $cell[1] }}</td>
                                        <td style="padding:9px 8px;text-align:center;color:#dc2626;border-left:1px solid #f3f4f6;">{{ $cell[3] }}</td>
                                    @endforeach
                                    <td style="padding:9px 8px;text-align:center;font-weight:bold;color:#1e3a5f;">{{ $row['total']['all'] }}</td>
                                    <td style="padding:9px 8px;text-align:center;color:#16a34a;">{{ $row['total'][2] }}</td>
                                    <td style="padding:9px 8px;text-align:center;color:#ca8a04;">{{ $row['total'][1] }}</td>
                                    <td style="padding:9px 8px;text-align:center;color:#dc2626;">{{ $row['total'][3] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- ─── گزارش جزئی (detailed) ─────────────────────────────────────────── --}}
    @if($detailedResult)
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
            <div style="padding:12px 20px;border-bottom:1px solid #f3f4f6;">
                <div style="font-size:16px;font-weight:bold;color:#1e3a5f;">کارنامه جزئی</div>
                <div style="font-size:12px;color:#6b7280;">{{ count($detailedResult) }} رکورد</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#1e3a5f;color:#fff;">
                            <th style="padding:10px 14px;text-align:right;white-space:nowrap;">تاریخ</th>
                            <th style="padding:10px 14px;text-align:right;white-space:nowrap;">همکار</th>
                            <th style="padding:10px 14px;text-align:right;white-space:nowrap;">نوع خدمت</th>
                            <th style="padding:10px 14px;text-align:right;white-space:nowrap;">نام مشتری</th>
                            <th style="padding:10px 14px;text-align:right;white-space:nowrap;">شماره حساب</th>
                            <th style="padding:10px 14px;text-align:center;white-space:nowrap;">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detailedResult as $idx => $row)
                            <tr style="background:{{ $idx % 2 === 0 ? '#f9fafb' : '#fff' }};border-bottom:1px solid #f3f4f6;">
                                <td style="padding:9px 14px;">{{ \Morilog\Jalali\Jalalian::fromDateTime($row['date'])->format('Y/m/d') }}</td>
                                <td style="padding:9px 14px;">{{ ($row['employee']['first_name'] ?? '') . ' ' . ($row['employee']['last_name'] ?? '') }}</td>
                                <td style="padding:9px 14px;">{{ $row['service_type']['name'] ?? '-' }}</td>
                                <td style="padding:9px 14px;">{{ $row['customer_name'] }}</td>
                                <td style="padding:9px 14px;">{{ $row['customer_account'] }}</td>
                                <td style="padding:9px 14px;text-align:center;">
                                    @php
                                        $sid = $row['validation_status_id'];
                                        $bg  = $sid === 2 ? '#dcfce7' : ($sid === 3 ? '#fee2e2' : '#fef9c3');
                                        $clr = $sid === 2 ? '#15803d' : ($sid === 3 ? '#b91c1c' : '#a16207');
                                    @endphp
                                    <span style="background:{{ $bg }};color:{{ $clr }};padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;">
                                        {{ $row['validation_status']['name'] ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(!$hasResult)
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:60px 20px;text-align:center;color:#9ca3af;font-size:13px;">
            فیلترها را تنظیم کنید و دکمه «نمایش گزارش» را بزنید.
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
