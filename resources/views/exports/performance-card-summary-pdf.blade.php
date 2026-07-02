<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: vazirmatn, sans-serif;
            direction: rtl;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        .header-box {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 5px;
            text-align: right;
        }
        .header-meta {
            font-size: 8px;
            color: #374151;
            line-height: 1.8;
            text-align: right;
        }
        .header-meta span { margin-left: 16px; }
        .summary-bar {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 14px;
            margin-bottom: 12px;
            font-size: 11px;
            display: flex;
            gap: 20px;
        }
        .summary-bar span { margin-left: 20px; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin: 12px 0 4px;
            padding: 6px 10px;
            background: #f3f4f6;
            border-right: 3px solid #1e3a5f;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            direction: rtl;
        }
        th {
            background: #1e3a5f;
            color: #ffffff;
            padding: 7px 10px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #1e3a5f;
        }
        th.col-name { text-align: right; }
        td {
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
        }
        td.col-name { text-align: right; font-weight: 500; }
        tr.even td { background: #f9fafb; }
        tr.odd  td { background: #ffffff; }
        tr.total-row td {
            background: #eff6ff;
            font-weight: bold;
            border-top: 2px solid #bfdbfe;
        }
        .num-all  { font-weight: bold; font-size: 10px; color: #1e3a5f; }
        .approved { color: #16a34a; }
        .pending  { color: #ca8a04; }
        .rejected { color: #dc2626; }
        .cell-breakdown { font-size: 8px; color: #9ca3af; margin-top: 2px; }
        .footer {
            margin-top: 14px;
            font-size: 7.5px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header-box">
        <div class="header-title">کارنامه کلی عملکرد — {{ $levelLabel }}: {{ $entityLabel }} (کد: {{ $entityId }})</div>
        <div class="header-meta">
            <span>بازه گزارش: {{ $fromJalali }} تا {{ $toJalali }}</span>
            <span>تاریخ اخذ گزارش: {{ $reportDate }}</span>
            <span>تهیه‌کننده: {{ $preparedBy }}</span>
        </div>
    </div>

    {{-- خلاصه جمع کل --}}
    <div style="border:1px solid #e5e7eb;padding:8px 14px;margin-bottom:12px;font-size:11px;text-align:right;">
        <span style="color:#374151;">مجموع کل: <strong>{{ $result['grand_total']['all'] }}</strong></span>
        <span style="margin-right:16px;" class="approved">✓ تایید: <strong>{{ $result['grand_total'][2] }}</strong></span>
        <span style="margin-right:16px;" class="pending">⏳ انتظار: <strong>{{ $result['grand_total'][1] }}</strong></span>
        <span style="margin-right:16px;" class="rejected">✗ رد: <strong>{{ $result['grand_total'][3] }}</strong></span>
        <span style="margin-right:16px;" class="rejected">⚑ کم‌عملکرد: <strong>{{ $result['low_performance_count'] }}</strong></span>
    </div>

    {{-- نمودارها --}}
    <div class="section-title">نمودار عملکرد</div>
    <table style="border:none;margin-bottom:12px;">
        <tr>
            <td style="border:none;text-align:center;width:50%;padding:0;">{!! $chartSvg['composition'] !!}</td>
            <td style="border:none;text-align:center;width:50%;padding:0;">{!! $chartSvg['top_entities'] !!}</td>
        </tr>
    </table>

    {{-- جدول جمع به تفکیک نوع خدمت --}}
    <div class="section-title">جمع کل به تفکیک نوع خدمت</div>
    <table>
        <thead>
            <tr>
                <th class="col-name">نوع خدمت</th>
                <th>کل</th>
                <th style="color:#86efac;">تایید</th>
                <th style="color:#fde68a;">انتظار</th>
                <th style="color:#fca5a5;">رد</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result['service_types'] as $i => $type)
                @php $cell = $result['totals'][$type]; @endphp
                <tr class="{{ $i % 2 === 0 ? 'even' : 'odd' }}">
                    <td class="col-name">{{ $type }}</td>
                    <td class="num-all">{{ $cell['all'] }}</td>
                    <td class="approved">{{ $cell[2] }}</td>
                    <td class="pending">{{ $cell[1] }}</td>
                    <td class="rejected">{{ $cell[3] }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="col-name">جمع کل</td>
                <td class="num-all" style="color:#1e3a5f;">{{ $result['grand_total']['all'] }}</td>
                <td class="approved">{{ $result['grand_total'][2] }}</td>
                <td class="pending">{{ $result['grand_total'][1] }}</td>
                <td class="rejected">{{ $result['grand_total'][3] }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ریز همکاران --}}
    @if(count($result['by_employee']) > 1)
        <div class="section-title">ریز عملکرد به تفکیک همکار</div>
        <table>
            <thead>
                <tr>
                    <th class="col-name" rowspan="2" style="vertical-align:middle;">کد پرسنلی</th>
                    <th class="col-name" rowspan="2" style="vertical-align:middle;">نام همکار</th>
                    <th class="col-name" rowspan="2" style="vertical-align:middle;">محل خدمت</th>
                    <th rowspan="2" style="vertical-align:middle;">کم‌عملکرد</th>
                    @foreach($result['service_types'] as $type)
                        <th colspan="4" style="border-bottom:1px solid #2d5a8e;">{{ $type }}</th>
                    @endforeach
                    <th colspan="4" style="border-bottom:1px solid #2d5a8e;">جمع</th>
                </tr>
                <tr>
                    @foreach($result['service_types'] as $type)
                        <th style="font-size:7px;color:#bfdbfe;">کل</th>
                        <th style="font-size:7px;color:#86efac;">تایید</th>
                        <th style="font-size:7px;color:#fde68a;">انتظار</th>
                        <th style="font-size:7px;color:#fca5a5;">رد</th>
                    @endforeach
                    <th style="font-size:7px;color:#bfdbfe;">کل</th>
                    <th style="font-size:7px;color:#86efac;">تایید</th>
                    <th style="font-size:7px;color:#fde68a;">انتظار</th>
                    <th style="font-size:7px;color:#fca5a5;">رد</th>
                </tr>
            </thead>
            <tbody>
                @foreach($result['by_employee'] as $idx => $emp)
                    <tr class="{{ $idx % 2 === 0 ? 'even' : 'odd' }}">
                        <td class="col-name">{{ $emp['personnel_code'] }}</td>
                        <td class="col-name" style="font-weight:500;">{{ $emp['full_name'] }}</td>
                        <td class="col-name" style="color:#6b7280;font-size:7px;">{{ $emp['workplace'] }}</td>
                        <td>
                            @if($emp['low_performance'])
                                <span class="rejected" style="font-weight:600;">کم‌عملکرد</span>
                            @else
                                —
                            @endif
                        </td>
                        @foreach($result['service_types'] as $type)
                            @php $cell = $emp['services'][$type]; @endphp
                            <td class="num-all">{{ $cell['all'] }}</td>
                            <td class="approved">{{ $cell[2] }}</td>
                            <td class="pending">{{ $cell[1] }}</td>
                            <td class="rejected">{{ $cell[3] }}</td>
                        @endforeach
                        <td class="num-all" style="color:#1e3a5f;">{{ $emp['total']['all'] }}</td>
                        <td class="approved">{{ $emp['total'][2] }}</td>
                        <td class="pending">{{ $emp['total'][1] }}</td>
                        <td class="rejected">{{ $emp['total'][3] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        این گزارش توسط سامانه ارزیابی عملکرد تهیه شده است — {{ $preparedBy }} — تاریخ: {{ $reportDate }}
    </div>

</body>
</html>
