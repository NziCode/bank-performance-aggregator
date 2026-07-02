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
            border: 1px solid #fecaca;
            background: #fff5f5;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            color: #7f1d1d;
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
            border: 1px solid #fecaca;
            background: #fee2e2;
            padding: 8px 14px;
            margin-bottom: 12px;
            font-size: 12px;
            color: #7f1d1d;
            font-weight: bold;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            direction: rtl;
        }
        th {
            background: #7f1d1d;
            color: #ffffff;
            padding: 7px 10px;
            text-align: right;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #7f1d1d;
        }
        th.center { text-align: center; }
        td {
            padding: 6px 10px;
            border: 1px solid #fee2e2;
            font-size: 9px;
            text-align: right;
        }
        td.center { text-align: center; color: #9ca3af; }
        tr.even td { background: #fff5f5; }
        tr.odd  td { background: #ffffff; }
        .empty-msg {
            padding: 30px;
            text-align: center;
            color: #16a34a;
            font-size: 11px;
            border: 1px solid #e5e7eb;
        }
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
        <div class="header-title">گزارش فاقد عملکرد — {{ $levelLabel }}: {{ $entityLabel }}</div>
        <div class="header-meta">
            <span>بازه گزارش: {{ $fromJalali }} تا {{ $toJalali }}</span>
            <span>تاریخ اخذ گزارش: {{ $reportDate }}</span>
            <span>تهیه‌کننده: {{ $preparedBy }}</span>
        </div>
    </div>

    @php
        $checkBy   = $result['check_by'] ?? 'employee';
        $entities  = $result['entities'] ?? [];
        $countUnit = match($checkBy) { 'branch' => 'شعبه', 'branch_office' => 'باجه', 'zone' => 'حوزه', default => 'نفر' };
        $col1      = match($checkBy) { 'branch' => 'کد شعبه', 'branch_office' => 'کد شعبه', 'zone' => 'کد حوزه', default => 'کد پرسنلی' };
        $col2      = match($checkBy) { 'branch' => 'نام شعبه', 'branch_office' => 'نام باجه', 'zone' => 'نام حوزه', default => 'نام همکار' };
        $col3      = match($checkBy) { 'branch' => 'حوزه', 'branch_office' => 'شعبه مادر', default => 'محل خدمت فعلی' };
        $hasExtra  = $checkBy !== 'zone';
        $emptyMsg  = match($checkBy) {
            'branch'        => 'همه شعب در این بازه عملکرد ثبت کرده‌اند.',
            'branch_office' => 'همه باجه‌ها در این بازه عملکرد ثبت کرده‌اند.',
            'zone'          => 'همه حوزه‌ها در این بازه عملکرد ثبت کرده‌اند.',
            default         => 'همه همکاران در این بازه عملکرد ثبت کرده‌اند.',
        };
    @endphp

    <div class="summary-bar">
        تعداد فاقد عملکرد در این بازه: {{ $result['count'] }} {{ $countUnit }}
    </div>

    @if($result['count'] > 0)
        <div style="font-size:11px;font-weight:bold;color:#7f1d1d;padding:5px 8px;background:#fee2e2;margin:0 0 8px;border-right:3px solid #7f1d1d;text-align:right;">
            نمودار توزیع
        </div>
        <div style="text-align:center;margin-bottom:12px;">{!! $chartSvg !!}</div>
    @endif

    @if($result['count'] === 0)
        <div class="empty-msg">✓ {{ $emptyMsg }}</div>
    @else
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:30px;">#</th>
                    <th style="width:80px;">{{ $col1 }}</th>
                    <th>{{ $col2 }}</th>
                    @if($hasExtra)
                        <th>{{ $col3 }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($entities as $idx => $item)
                    <tr class="{{ $idx % 2 === 0 ? 'even' : 'odd' }}">
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $item['code'] }}</td>
                        <td style="font-weight:500;">{{ $item['name'] }}</td>
                        @if($hasExtra)
                            <td style="color:#6b7280;">{{ $item['extra'] }}</td>
                        @endif
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
