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
            font-size: 13px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 4px;
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
            padding: 7px 12px;
            border: 1px solid #e5e7eb;
            margin-bottom: 12px;
            font-size: 11px;
            text-align: right;
        }
        .summary-bar span { margin-right: 16px; }
        table {
            width: 100%;
            border-collapse: collapse;
            direction: rtl;
        }
        th {
            background: #1e3a5f;
            color: #fff;
            padding: 7px 10px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #1e3a5f;
            text-align: center;
        }
        th.col-label { text-align: right; }
        td {
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            font-size: 9px;
            text-align: center;
        }
        td.col-label { text-align: right; font-weight: 500; }
        tr.even td { background: #f9fafb; }
        tr.odd  td { background: #fff; }
        tr.total-row td { background: #eff6ff; font-weight: bold; border-top: 2px solid #bfdbfe; }
        .num-all  { font-weight: bold; font-size: 10px; color: #1e3a5f; }
        .sub      { font-size: 8px; color: #9ca3af; margin-top: 2px; }
        .approved { color: #16a34a; }
        .pending  { color: #ca8a04; }
        .rejected { color: #dc2626; }
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
        <div class="header-title">کارنامه عملکرد — {{ $levelLabel }}: {{ $entityLabel }} — ریزبندی: {{ $result['breakdown_label'] }}</div>
        <div class="header-meta">
            <span>بازه گزارش: {{ $fromJalali }} تا {{ $toJalali }}</span>
            <span>تاریخ اخذ گزارش: {{ $reportDate }}</span>
            <span>تهیه‌کننده: {{ $preparedBy }}</span>
        </div>
    </div>

    <div class="summary-bar">
        <span style="color:#374151;">مجموع کل: <strong>{{ $result['grand_total']['all'] }}</strong></span>
        <span class="approved">✓ تایید: <strong>{{ $result['grand_total'][2] }}</strong></span>
        <span class="pending">⏳ انتظار: <strong>{{ $result['grand_total'][1] }}</strong></span>
        <span class="rejected">✗ رد: <strong>{{ $result['grand_total'][3] }}</strong></span>
        <span style="color:#b91c1c;">⚑ کم‌عملکرد: <strong>{{ $result['low_performance_count'] }}</strong></span>
    </div>

    <div class="section-title" style="font-size:10px;font-weight:bold;color:#1e3a5f;padding:5px 8px;background:#dbeafe;margin:10px 0 4px;border-right:3px solid #1e3a5f;">
        نمودار عملکرد
    </div>
    <div style="text-align:center;margin-bottom:12px;">{!! $chartSvg !!}</div>

    <div class="section-title" style="font-size:10px;font-weight:bold;color:#1e3a5f;padding:5px 8px;background:#dbeafe;margin:10px 0 4px;border-right:3px solid #1e3a5f;">
        آمار به تفکیک {{ $result['breakdown_label'] }}
    </div>
    <table style="margin-bottom:16px;">
        <thead>
            <tr>
                <th class="col-label" rowspan="2" style="vertical-align:middle;">{{ $result['breakdown_label'] }}</th>
                <th rowspan="2" style="vertical-align:middle;">کم‌عملکرد</th>
                @foreach($result['service_types'] as $type)
                    <th colspan="4" style="border-bottom:1px solid #2d5a8e;">{{ $type }}</th>
                @endforeach
                <th colspan="4" style="border-bottom:1px solid #2d5a8e;">جمع کل</th>
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
            @foreach($result['rows'] as $idx => $row)
                <tr class="{{ $idx % 2 === 0 ? 'even' : 'odd' }}">
                    <td class="col-label">{{ $row['label'] }}</td>
                    <td class="center">
                        @if($row['low_performance'])
                            <span class="badge rejected">کم‌عملکرد</span>
                        @else
                            —
                        @endif
                    </td>
                    @foreach($result['service_types'] as $type)
                        @php $cell = $row['services'][$type]; @endphp
                        <td class="num-all">{{ $cell['all'] }}</td>
                        <td class="approved">{{ $cell[2] }}</td>
                        <td class="pending">{{ $cell[1] }}</td>
                        <td class="rejected">{{ $cell[3] }}</td>
                    @endforeach
                    <td class="num-all" style="color:#1e3a5f;">{{ $row['grand_total']['all'] }}</td>
                    <td class="approved">{{ $row['grand_total'][2] }}</td>
                    <td class="pending">{{ $row['grand_total'][1] }}</td>
                    <td class="rejected">{{ $row['grand_total'][3] }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="col-label" colspan="2">جمع کل</td>
                @foreach($result['service_types'] as $type)
                    @php
                        $all = array_sum(array_map(fn($r) => $r['services'][$type]['all'], $result['rows']));
                        $app = array_sum(array_map(fn($r) => $r['services'][$type][2], $result['rows']));
                        $pen = array_sum(array_map(fn($r) => $r['services'][$type][1], $result['rows']));
                        $rej = array_sum(array_map(fn($r) => $r['services'][$type][3], $result['rows']));
                    @endphp
                    <td class="num-all">{{ $all }}</td>
                    <td class="approved">{{ $app }}</td>
                    <td class="pending">{{ $pen }}</td>
                    <td class="rejected">{{ $rej }}</td>
                @endforeach
                <td class="num-all" style="color:#1e3a5f;">{{ $result['grand_total']['all'] }}</td>
                <td class="approved">{{ $result['grand_total'][2] }}</td>
                <td class="pending">{{ $result['grand_total'][1] }}</td>
                <td class="rejected">{{ $result['grand_total'][3] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        این گزارش توسط سامانه ارزیابی عملکرد تهیه شده است — {{ $preparedBy }} — تاریخ: {{ $reportDate }}
    </div>

</body>
</html>
