<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: vazirmatn, sans-serif;
            direction: rtl;
            font-size: 9px;
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
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin: 0 0 6px;
            padding: 6px 10px;
            background: #f3f4f6;
            border-right: 3px solid #1e3a5f;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            direction: rtl;
        }
        th {
            background: #1e3a5f;
            color: #ffffff;
            padding: 7px 10px;
            text-align: right;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #1e3a5f;
            white-space: nowrap;
        }
        th.center { text-align: center; }
        td {
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            text-align: right;
            font-size: 9px;
        }
        td.center { text-align: center; }
        tr.even td { background: #f9fafb; }
        tr.odd  td { background: #ffffff; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 8px;
            font-weight: bold;
        }
        .approved { background: #dcfce7; color: #15803d; }
        .pending  { background: #fef9c3; color: #a16207; }
        .rejected { background: #fee2e2; color: #b91c1c; }
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
        <div class="header-title">کارنامه جزئی عملکرد — {{ $levelLabel }}: {{ $entityLabel }} (کد: {{ $entityId }})</div>
        <div class="header-meta">
            <span>بازه گزارش: {{ $fromJalali }} تا {{ $toJalali }}</span>
            <span>تعداد رکورد: {{ count($records) }}</span>
            <span>تاریخ اخذ گزارش: {{ $reportDate }}</span>
            <span>تهیه‌کننده: {{ $preparedBy }}</span>
        </div>
    </div>

    <div class="section-title">کارنامه جزئی — {{ $entityLabel }}</div>

    <table>
        <thead>
            <tr>
                <th style="white-space:nowrap;">تاریخ</th>
                <th style="white-space:nowrap;">همکار</th>
                <th style="white-space:nowrap;">نوع خدمت</th>
                <th style="white-space:nowrap;">نام مشتری</th>
                <th style="white-space:nowrap;">شماره حساب</th>
                <th class="center" style="white-space:nowrap;">وضعیت</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $idx => $r)
                <tr class="{{ $idx % 2 === 0 ? 'even' : 'odd' }}">
                    <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($r->date)->format('Y/m/d') }}</td>
                    <td>{{ $r->employee?->full_name }}</td>
                    <td>{{ $r->serviceType?->name }}</td>
                    <td>{{ $r->customer_name }}</td>
                    <td>{{ $r->customer_account }}</td>
                    <td class="center">
                        @php $sid = $r->validation_status_id; @endphp
                        <span class="badge {{ $sid === 2 ? 'approved' : ($sid === 3 ? 'rejected' : 'pending') }}">
                            {{ $r->validationStatus?->name }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        این گزارش توسط سامانه ارزیابی عملکرد تهیه شده است — {{ $preparedBy }} — تاریخ: {{ $reportDate }}
    </div>

</body>
</html>
