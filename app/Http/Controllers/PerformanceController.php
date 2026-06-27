<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use App\Models\RejectionReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    // لیست عملکردها با فیلتر
    public function index(Request $request): JsonResponse
    {
        $query = Performance::with([
            'employee',
            'branch',
            'serviceType',
            'validationStatus',
            'rejectionReason',
        ]);

        if ($request->filled('status')) {
            $query->where('validation_status_id', $request->status);
        }

        if ($request->filled('branch_code')) {
            $query->forBranch($request->branch_code);
        }

        if ($request->filled('personnel_code')) {
            $query->forEmployee($request->personnel_code);
        }

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->forPeriod($request->from, $request->to);
        }

        $performances = $query->orderByDesc('date')->paginate(20);

        return response()->json($performances);
    }

    // تایید عملکرد
    public function approve(Request $request, Performance $performance): JsonResponse
    {
        if (! $performance->isPending()) {
            return response()->json([
                'message' => 'این عملکرد قبلاً بررسی شده است',
            ], 422);
        }

        $performance->update([
            'validation_status_id' => 2,
            'rejection_reason_id'  => null,
            'validated_by'         => $request->user()->personnel_code,
            'validated_at'         => now(),
        ]);

        return response()->json([
            'message' => 'عملکرد با موفقیت تایید شد',
        ]);
    }

    // رد عملکرد
    public function reject(Request $request, Performance $performance): JsonResponse
    {
        $request->validate([
            'rejection_reason_id' => ['required', 'exists:rejection_reasons,id'],
        ]);

        if (! $performance->isPending()) {
            return response()->json([
                'message' => 'این عملکرد قبلاً بررسی شده است',
            ], 422);
        }

        $performance->update([
            'validation_status_id' => 3,
            'rejection_reason_id'  => $request->rejection_reason_id,
            'validated_by'         => $request->user()->personnel_code,
            'validated_at'         => now(),
        ]);

        return response()->json([
            'message' => 'عملکرد رد شد',
        ]);
    }

    // تایید/رد دسته‌ای
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:performances,id'],
        ]);

        $count = Performance::whereIn('id', $request->ids)
            ->where('validation_status_id', 1)
            ->update([
                'validation_status_id' => 2,
                'validated_by'         => $request->user()->personnel_code,
                'validated_at'         => now(),
            ]);

        return response()->json([
            'message' => "{$count} عملکرد تایید شد",
        ]);
    }

    public function bulkReject(Request $request): JsonResponse
    {
        $request->validate([
            'ids'                 => ['required', 'array', 'min:1'],
            'ids.*'               => ['integer', 'exists:performances,id'],
            'rejection_reason_id' => ['required', 'exists:rejection_reasons,id'],
        ]);

        $count = Performance::whereIn('id', $request->ids)
            ->where('validation_status_id', 1)
            ->update([
                'validation_status_id' => 3,
                'rejection_reason_id'  => $request->rejection_reason_id,
                'validated_by'         => $request->user()->personnel_code,
                'validated_at'         => now(),
            ]);

        return response()->json([
            'message' => "{$count} عملکرد رد شد",
        ]);
    }

    // لیست دلایل رد
    public function rejectionReasons(): JsonResponse
    {
        return response()->json(RejectionReason::all());
    }
}
