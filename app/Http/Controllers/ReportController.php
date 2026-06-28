<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Performance;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    // --- Web ---

    public function webIndex(Request $request): View
    {
        $serviceTypes = ServiceType::all();
        $branches     = Branch::orderBy('name')->get();
        $data         = null;

        if ($request->filled('from') && $request->filled('to')) {
            $data = $this->buildReport($request);
        }

        return view('reports.index', compact('serviceTypes', 'branches', 'data', ));
    }

    // --- API ---

    public function summary(Request $request): JsonResponse
    {
        $from  = $request->input('from');
        $to    = $request->input('to');
        $query = Performance::approved();

        if ($from && $to) {
            $query->forPeriod($from, $to);
        }

        return response()->json([
            'total'            => $query->count(),
            'unique_employees' => $query->distinct('personnel_code')->count(),
            'unique_branches'  => $query->distinct('branch_code')->count(),
            'by_service_type'  => (clone $query)
                ->select('service_type_id', DB::raw('COUNT(*) as count'))
                ->with('serviceType')
                ->groupBy('service_type_id')
                ->get()
                ->map(fn($r) => [
                    'service' => $r->serviceType->name,
                    'count'   => $r->count,
                ]),
        ]);
    }

    public function byEmployee(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);

        $data = $this->buildReport($request);

        return response()->json([
            'from'  => $request->from,
            'to'    => $request->to,
            'count' => count($data),
            'data'  => $data,
        ]);
    }

    public function byBranch(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);

        $data = Performance::approved()
            ->forPeriod($request->from, $request->to)
            ->select([
                'branch_code',
                'service_type_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('COUNT(DISTINCT personnel_code) as employee_count'),
            ])
            ->with(['branch', 'serviceType'])
            ->groupBy('branch_code', 'service_type_id')
            ->get()
            ->groupBy('branch_code')
            ->map(function ($records) {
                $first = $records->first();
                return [
                    'branch_code'    => $first->branch_code,
                    'branch_name'    => $first->branch?->name,
                    'zone'           => $first->branch?->zone?->name,
                    'employee_count' => $first->employee_count,
                    'services'       => $records->mapWithKeys(fn($r) => [
                        $r->serviceType->name => $r->count,
                    ]),
                    'total' => $records->sum('count'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return response()->json([
            'from'  => $request->from,
            'to'    => $request->to,
            'count' => $data->count(),
            'data'  => $data,
        ]);
    }

    // --- Helper ---

    private function buildReport(Request $request): array
    {
        $serviceTypes = ServiceType::pluck('name');

        $records = Performance::approved()
            ->forPeriod($request->from, $request->to)
            ->when($request->filled('branch_code'), fn($q) => $q->forBranch($request->branch_code))
            ->select([
                'personnel_code',
                'service_type_id',
                DB::raw('COUNT(*) as count'),
            ])
            ->with(['employee.branch', 'serviceType'])
            ->groupBy('personnel_code', 'service_type_id')
            ->get()
            ->groupBy('personnel_code');

        $result = [];
        foreach ($records as $personnelCode => $rows) {
            $first    = $rows->first();
            $services = [];

            foreach ($serviceTypes as $type) {
                $services[$type] = $rows->first(fn($r) => $r->serviceType?->name === $type)?->count ?? 0;
            }

            $result[] = [
                'personnel_code' => $personnelCode,
                'full_name'      => $first->employee?->full_name,
                'branch'         => $first->employee?->branch?->name,
                'services'       => $services,
                'total'          => $rows->sum('count'),
            ];
        }

        usort($result, fn($a, $b) => $b['total'] <=> $a['total']);

        return $result;
    }
}
