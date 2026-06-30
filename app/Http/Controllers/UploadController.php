<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessUploadedFileJob;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UploadController extends Controller
{
    // --- API ---

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1', 'max:50'],
            'files.*' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'period'  => ['nullable', 'date'],
        ]);

        $uploadIds = [];

        foreach ($request->file('files') as $file) {
            $period = $request->input('period', now()->toDateString());
            $path   = $file->store("excel-uploads/{$period}", 'local');

            $upload = Upload::create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'period'            => $period,
                'uploaded_by'       => auth()->id(),
            ]);

            ProcessUploadedFileJob::dispatch($upload->id);
            $uploadIds[] = $upload->id;
        }

        return response()->json([
            'message'    => count($uploadIds) . ' فایل در صف پردازش قرار گرفت',
            'upload_ids' => $uploadIds,
        ], 202);
    }

    public function show(Upload $upload): JsonResponse
    {
        return response()->json([
            'id'             => $upload->id,
            'filename'       => $upload->original_filename,
            'status'         => $upload->status,
            'rows_processed' => $upload->rows_processed,
            'rows_rejected'  => $upload->rows_rejected,
            'errors'         => $upload->errors ?? [],
            'failure_reason' => $upload->failure_reason,
            'processed_at'   => $upload->processed_at?->toDateTimeString(),
        ]);
    }

    // --- Web (در صورت استفاده مستقل از Filament) ---

    public function webIndex(): View
    {
        $uploads = Upload::orderByDesc('created_at')->paginate(15);
        return view('uploads.index', compact('uploads'));
    }

    public function webStore(Request $request): RedirectResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1', 'max:50'],
            'files.*' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'period'  => ['nullable', 'date'],
        ]);

        foreach ($request->file('files') as $file) {
            $period = $request->input('period', now()->toDateString());
            $path   = $file->store("excel-uploads/{$period}", 'local');

            $upload = Upload::create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'period'            => $period,
                'uploaded_by'       => auth()->id(),
            ]);

            ProcessUploadedFileJob::dispatch($upload->id);
        }

        return redirect()->route('uploads.index')
            ->with('success', 'فایل‌ها با موفقیت در صف پردازش قرار گرفتند');
    }
}
