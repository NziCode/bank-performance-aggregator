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
        <div class="header-title">کارنامه عملکرد — {{ $levelLabel }}: {{ $entityLabel }} — ریزبندی بر اساس {{ $result['breakdown_label'] }}</div>
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
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-label" style="white-space:nowrap;">{{ $result['breakdown_label'] }}</th>
                @foreach($result['service_types'] as $type)
                    <th style="white-space:nowrap;">{{ $type }}</th>
                @endforeach
                <th style="white-space:nowrap;">جمع کل</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result['rows'] as $idx => $row)
                <tr class="{{ $idx % 2 === 0 ? 'even' : 'odd' }}">
                    <td class="col-label">{{ $row['label'] }}</td>
                    @foreach($result['service_types'] as $type)
                        @php $cell = $row['services'][$type]; @endphp
                        <td>
                            <div class="num-all">{{ $cell['all'] }}</div>
                            <div class="sub">
                                <span class="approved">{{ $cell[2] }}</span>/<span class="pending">{{ $cell[1] }}</span>/<span class="rejected">{{ $cell[3] }}</span>
                            </div>
                        </td>
                    @endforeach
                    <td>
                        <div class="num-all">{{ $row['grand_total']['all'] }}</div>
                        <div class="sub">
                            <span class="approved">{{ $row['grand_total'][2] }}</span>/<span class="pending">{{ $row['grand_total'][1] }}</span>/<span class="rejected">{{ $row['grand_total'][3] }}</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="col-label">جمع کل</td>
                @foreach($result['service_types'] as $type)
                    @php
                        $all = array_sum(array_map(fn($r) => $r['services'][$type]['all'], $result['rows']));
                        $app = array_sum(array_map(fn($r) => $r['services'][$type][2], $result['rows']));
                        $pen = array_sum(array_map(fn($r) => $r['services'][$type][1], $result['rows']));
                        $rej = array_sum(array_map(fn($r) => $r['services'][$type][3], $result['rows']));
                    @endphp
                    <td>
                        <div class="num-all">{{ $all }}</div>
                        <div class="sub">
                            <span class="approved">{{ $app }}</span>/<span class="pending">{{ $pen }}</span>/<span class="rejected">{{ $rej }}</span>
                        </div>
                    </td>
                @endforeach
                <td class="num-all" style="color:#1e3a5f;">{{ $result['grand_total']['all'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        این گزارش توسط سامانه ارزیابی عملکرد تهیه شده است — {{ $preparedBy }} — تاریخ: {{ $reportDate }}
    </div>

</body>
</html>
