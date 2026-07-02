<x-filament-panels::page>

    {{-- Chart.js CDN — loaded once, available for all charts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- ─── فرم فیلتر ──────────────────────────────────────────────────────── --}}
    <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:24px;">
        <form wire:submit="generate">
            {{ $this->form }}
            <div style="margin-top:16px;">
                <x-filament::button type="submit" icon="heroicon-o-chart-bar">
                    نمایش داشبورد
                </x-filament::button>
            </div>
        </form>
    </div>

    @if($dashboardData)

        @php
            $total   = $dashboardData['total_employees'];
            $noPerf  = $dashboardData['no_performance'];
            $hasPerf = $dashboardData['has_performance'];
            $pct     = $total > 0 ? round($noPerf / $total * 100, 1) : 0;
        @endphp

        {{-- ─── نوار دوره ─────────────────────────────────────────────────── --}}
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 20px;margin-bottom:20px;font-size:12px;color:#1e3a5f;display:flex;gap:24px;flex-wrap:wrap;">
            <span>دوره: <strong>{{ $periodLabel }}</strong></span>
            <span>از: <strong>{{ $fromLabel }}</strong></span>
            <span>تا: <strong>{{ $toLabel }}</strong></span>
        </div>

        {{-- ─── کارت‌های خلاصه ──────────────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">

            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px 24px;border-top:4px solid #6b7280;">
                <div style="font-size:11px;color:#6b7280;margin-bottom:8px;font-weight:500;">کل همکاران</div>
                <div style="font-size:32px;font-weight:bold;color:#374151;">{{ number_format($total) }}</div>
            </div>

            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px 24px;border-top:4px solid #dc2626;">
                <div style="font-size:11px;color:#dc2626;margin-bottom:8px;font-weight:500;">فاقد عملکرد</div>
                <div style="font-size:32px;font-weight:bold;color:#dc2626;">{{ number_format($noPerf) }}</div>
            </div>

            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px 24px;border-top:4px solid #16a34a;">
                <div style="font-size:11px;color:#16a34a;margin-bottom:8px;font-weight:500;">دارای عملکرد</div>
                <div style="font-size:32px;font-weight:bold;color:#16a34a;">{{ number_format($hasPerf) }}</div>
            </div>

            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px 24px;border-top:4px solid #d97706;">
                <div style="font-size:11px;color:#d97706;margin-bottom:8px;font-weight:500;">درصد فاقد عملکرد</div>
                <div style="font-size:32px;font-weight:bold;color:#d97706;">{{ $pct }}٪</div>
                <div style="margin-top:8px;height:6px;background:#f3f4f6;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;background:#d97706;border-radius:99px;"></div>
                </div>
            </div>
        </div>

        {{-- ─── ردیف ۱: دونات + محل خدمت ─────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

            {{-- دونات: نسبت --}}
            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;">
                <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:16px;">نسبت کلی عملکرد</div>
                <div wire:key="chart-doughnut-{{ $generateCount }}"
                     data-no="{{ $noPerf }}"
                     data-has="{{ $hasPerf }}"
                     x-data="{}"
                     x-init="$nextTick(() => {
                         new Chart($refs.canvas, {
                             type: 'doughnut',
                             data: {
                                 labels: ['فاقد عملکرد', 'دارای عملکرد'],
                                 datasets: [{ data: [$el.dataset.no, $el.dataset.has], backgroundColor: ['#dc2626','#16a34a'], borderWidth: 0 }]
                             },
                             options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                         });
                     })"
                     style="display:flex;justify-content:center;max-height:280px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            {{-- پای: محل خدمت --}}
            @php
                $wtLabels = json_encode(collect($dashboardData['by_workplace_type'])->pluck('label')->all());
                $wtCounts = json_encode(collect($dashboardData['by_workplace_type'])->pluck('count')->all());
            @endphp
            <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;">
                <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:16px;">تفکیک بر اساس محل خدمت</div>
                <div wire:key="chart-workplace-{{ $generateCount }}"
                     data-labels="{{ htmlspecialchars($wtLabels) }}"
                     data-counts="{{ htmlspecialchars($wtCounts) }}"
                     x-data="{}"
                     x-init="$nextTick(() => {
                         new Chart($refs.canvas, {
                             type: 'pie',
                             data: {
                                 labels: JSON.parse($el.dataset.labels),
                                 datasets: [{ data: JSON.parse($el.dataset.counts), backgroundColor: ['#1e3a5f','#2563eb','#7c3aed','#ca8a04','#16a34a'], borderWidth: 0 }]
                             },
                             options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                         });
                     })"
                     style="display:flex;justify-content:center;max-height:280px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        {{-- ─── نمودار حوزه ─────────────────────────────────────────────── --}}
        @if(count($dashboardData['by_zone']) > 0)
        @php
            $zoneH = max(200, count($dashboardData['by_zone']) * 40);
            $zLabels = json_encode(collect($dashboardData['by_zone'])->pluck('label')->all());
            $zCounts = json_encode(collect($dashboardData['by_zone'])->pluck('count')->all());
        @endphp
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:16px;">فاقد عملکرد به تفکیک حوزه</div>
            <div wire:key="chart-zone-{{ $generateCount }}"
                 data-labels="{{ htmlspecialchars($zLabels) }}"
                 data-counts="{{ htmlspecialchars($zCounts) }}"
                 x-data="{}"
                 x-init="$nextTick(() => {
                     new Chart($refs.canvas, {
                         type: 'bar',
                         data: {
                             labels: JSON.parse($el.dataset.labels),
                             datasets: [{ label: 'فاقد عملکرد', data: JSON.parse($el.dataset.counts), backgroundColor: '#1e3a5f', borderRadius: 4 }]
                         },
                         options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { stepSize: 1 }, beginAtZero: true } } }
                     });
                 })">
                <canvas x-ref="canvas" style="max-height:{{ $zoneH }}px;"></canvas>
            </div>
        </div>
        @endif

        {{-- ─── نمودار شعبه (top 15) ────────────────────────────────────── --}}
        @if(count($dashboardData['by_branch']) > 0)
        @php
            $branchH = max(250, count($dashboardData['by_branch']) * 36);
            $bLabels = json_encode(collect($dashboardData['by_branch'])->pluck('label')->all());
            $bCounts = json_encode(collect($dashboardData['by_branch'])->pluck('count')->all());
        @endphp
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:4px;">
                فاقد عملکرد به تفکیک شعبه
                <span style="font-size:11px;font-weight:400;color:#9ca3af;">(تا ۱۵ شعبه)</span>
            </div>
            <div wire:key="chart-branch-{{ $generateCount }}"
                 data-labels="{{ htmlspecialchars($bLabels) }}"
                 data-counts="{{ htmlspecialchars($bCounts) }}"
                 x-data="{}"
                 x-init="$nextTick(() => {
                     new Chart($refs.canvas, {
                         type: 'bar',
                         data: {
                             labels: JSON.parse($el.dataset.labels),
                             datasets: [{ label: 'فاقد عملکرد', data: JSON.parse($el.dataset.counts), backgroundColor: '#dc2626', borderRadius: 4 }]
                         },
                         options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { stepSize: 1 }, beginAtZero: true } } }
                     });
                 })">
                <canvas x-ref="canvas" style="max-height:{{ $branchH }}px;"></canvas>
            </div>
        </div>
        @endif

        {{-- ─── نمودار نوع خدمت ─────────────────────────────────────────── --}}
        @php
            $stLabels = json_encode(collect($dashboardData['by_service_type'])->pluck('name')->all());
            $stCounts = json_encode(collect($dashboardData['by_service_type'])->pluck('count')->all());
        @endphp
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:16px;">فاقد عملکرد به تفکیک نوع خدمت</div>
            <div wire:key="chart-service-{{ $generateCount }}"
                 data-labels="{{ htmlspecialchars($stLabels) }}"
                 data-counts="{{ htmlspecialchars($stCounts) }}"
                 x-data="{}"
                 x-init="$nextTick(() => {
                     new Chart($refs.canvas, {
                         type: 'bar',
                         data: {
                             labels: JSON.parse($el.dataset.labels),
                             datasets: [{ label: 'فاقد عملکرد', data: JSON.parse($el.dataset.counts), backgroundColor: '#7c3aed', borderRadius: 4 }]
                         },
                         options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { stepSize: 1 }, beginAtZero: true } } }
                     });
                 })">
                <canvas x-ref="canvas" style="max-height:320px;"></canvas>
            </div>
        </div>

        {{-- ─── لیست همکاران فاقد عملکرد ──────────────────────────────── --}}
        @if($noPerf > 0)
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
            <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span style="font-size:15px;font-weight:bold;color:#7f1d1d;">لیست همکاران فاقد عملکرد</span>
                <span style="background:#fee2e2;color:#b91c1c;padding:2px 12px;border-radius:99px;font-size:12px;font-weight:600;">{{ $noPerf }} نفر</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#7f1d1d;color:#fff;">
                            <th style="padding:10px 16px;text-align:right;width:48px;">#</th>
                            <th style="padding:10px 16px;text-align:right;">کد پرسنلی</th>
                            <th style="padding:10px 16px;text-align:right;">نام همکار</th>
                            <th style="padding:10px 16px;text-align:right;">محل خدمت فعلی</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dashboardData['employees'] as $idx => $emp)
                            <tr style="background:{{ $idx % 2 === 0 ? '#fff5f5' : '#fff' }};border-bottom:1px solid #fee2e2;">
                                <td style="padding:9px 16px;color:#9ca3af;font-size:11px;">{{ $idx + 1 }}</td>
                                <td style="padding:9px 16px;font-family:monospace;color:#374151;">{{ $emp['personnel_code'] }}</td>
                                <td style="padding:9px 16px;font-weight:500;">{{ $emp['full_name'] }}</td>
                                <td style="padding:9px 16px;color:#6b7280;font-size:12px;">{{ $emp['workplace'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:40px;text-align:center;color:#16a34a;font-size:14px;font-weight:600;">
                ✓ همه همکاران در این بازه عملکرد ثبت کرده‌اند.
            </div>
        @endif

    @else
        <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:60px 20px;text-align:center;color:#9ca3af;font-size:13px;">
            بازه زمانی را انتخاب کنید و دکمه «نمایش داشبورد» را بزنید.
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
