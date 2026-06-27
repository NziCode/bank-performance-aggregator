<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // داشبورد خلاصه
    public function summary(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

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
            'by_status' => Performance::when($from && $to, fn($q) => $q->forPeriod($from, $to))
                ->select('validation_status_id', DB::raw('COUNT(*) as count'))
                ->groupBy('validation_status_id')
                ->with('validationStatus')
                ->get()
                ->map(fn($r) => [
                    'status' => $r->validationStatus->name,
                    'count'  => $r->count,
                ]),
        ]);
    }

    // گزارش به تفکیک کارمند
    public function byEmployee(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);

        $data = Performance::approved()
            ->forPeriod($request->from, $request->to)
            ->select([
                'personnel_code',
                'service_type_id',
                DB::raw('COUNT(*) as count'),
            ])
            ->with(['employee', 'serviceType'])
            ->groupBy('personnel_code', 'service_type_id')
            ->get()
            ->groupBy('personnel_code')
            ->map(function ($records) {
                $first = $records->first();
                return [
                    'personnel_code' => $first->personnel_code,
                    'full_name'      => $first->employee?->full_name,
                    'branch'         => $first->employee?->branch?->name,
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

    // گزارش به تفکیک شعبه
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
}
