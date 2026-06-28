<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use App\Models\RejectionReason;
use App\Models\ServiceType;
use App\Models\ValidationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    // --- Web ---

    public function webIndex(Request $request): View
    {
        $query = Performance::with([
            'employee',
            'branch',
            'serviceType',
            'validationStatus',
        ]);

        if ($request->filled('status')) {
            $query->where('validation_status_id', $request->status);
        }

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->forPeriod($request->from, $request->to);
        }

        $performances = $query->orderByDesc('date')->paginate(20);
        $statuses     = ValidationStatus::all();
        $serviceTypes = ServiceType::all();

        return view('performances.index', compact('performances', 'statuses', 'serviceTypes'));
    }

    // --- API ---

    public function index(Request $request): JsonResponse
    {
        $query = Performance::with([
            'employee', 'branch', 'serviceType', 'validationStatus', 'rejectionReason',
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

        return response()->json($query->orderByDesc('date')->paginate(20));
    }

    public function approve(Request $request, Performance $performance): RedirectResponse|JsonResponse
    {
        $performance->update([
            'validation_status_id' => 2,
            'rejection_reason_id'  => null,
            'validated_by'         => auth()->id(),
            'validated_at'         => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'تایید شد']);
        }

        return back()->with('success', 'عملکرد تایید شد');
    }

    public function reject(Request $request, Performance $performance): RedirectResponse|JsonResponse
    {
        $request->validate([
            'rejection_reason_id' => ['required', 'exists:rejection_reasons,id'],
        ]);

        $performance->update([
            'validation_status_id' => 3,
            'rejection_reason_id'  => $request->rejection_reason_id,
            'validated_by'         => auth()->id(),
            'validated_at'         => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'رد شد']);
        }

        return back()->with('success', 'عملکرد رد شد');
    }

    public function bulkApprove(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:performances,id'],
        ]);

        $count = Performance::whereIn('id', $request->ids)
            ->where('validation_status_id', 1)
            ->update([
                'validation_status_id' => 2,
                'validated_by'         => auth()->id(),
                'validated_at'         => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$count} عملکرد تایید شد"]);
        }

        return back()->with('success', "{$count} عملکرد تایید شد");
    }

    public function bulkReject(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ids'                 => ['required', 'array'],
            'ids.*'               => ['integer', 'exists:performances,id'],
            'rejection_reason_id' => ['required', 'exists:rejection_reasons,id'],
        ]);

        $count = Performance::whereIn('id', $request->ids)
            ->where('validation_status_id', 1)
            ->update([
                'validation_status_id' => 3,
                'rejection_reason_id'  => $request->rejection_reason_id,
                'validated_by'         => auth()->id(),
                'validated_at'         => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$count} عملکرد رد شد"]);
        }

        return back()->with('success', "{$count} عملکرد رد شد");
    }

    public function rejectionReasons(): JsonResponse
    {
        return response()->json(RejectionReason::all());
    }
}
