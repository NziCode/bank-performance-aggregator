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
            'period'  => ['nullable', 'string', 'regex:/^\d{6}$/'],
        ]);

        $uploadIds = [];

        foreach ($request->file('files') as $file) {
            $period = $request->input('period', now()->format('Ym'));
            $path   = $file->store("excel-uploads/{$period}", 'local');

            $upload = Upload::create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'branch_code'       => $this->extractBranchCode($file->getClientOriginalName()),
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

    // --- Web ---

    public function webIndex(): View
    {
        $uploads = Upload::with('branch')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('uploads.index', compact('uploads'));
    }

    public function webStore(Request $request): RedirectResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1', 'max:50'],
            'files.*' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'period'  => ['nullable', 'string', 'regex:/^\d{6}$/'],
        ]);

        foreach ($request->file('files') as $file) {
            $period = $request->input('period', now()->format('Ym'));
            $path   = $file->store("excel-uploads/{$period}", 'local');

            $upload = Upload::create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'branch_code'       => $this->extractBranchCode($file->getClientOriginalName()),
                'period'            => $period,
                'uploaded_by'       => auth()->id(),
            ]);

            ProcessUploadedFileJob::dispatch($upload->id);
        }

        return redirect()->route('uploads.index')
            ->with('success', 'فایل‌ها با موفقیت در صف پردازش قرار گرفتند');
    }

    // --- Helper ---

    private function extractBranchCode(string $filename): ?int
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        return is_numeric($name) ? (int) $name : null;
    }
}
