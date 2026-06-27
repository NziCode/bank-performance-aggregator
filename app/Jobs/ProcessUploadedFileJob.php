<?php

namespace App\Jobs;

use App\Exceptions\InvalidExcelStructureException;
use App\Models\Performance;
use App\Models\ServiceType;
use App\Models\Upload;
use App\Services\Excel\ExcelParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 3;

    public function __construct(private readonly int $uploadId)
    {
        $this->onQueue('excel-processing');
    }

    public function handle(ExcelParserService $parser): void
    {
        $upload = Upload::findOrFail($this->uploadId);
        $upload->markAsProcessing();

        try {
            $rows   = $parser->parse(Storage::path($upload->stored_path));
            $errors = [];
            $saved  = 0;

            // کش انواع خدمات
            $serviceTypes = ServiceType::pluck('id', 'name');

            DB::transaction(function () use ($rows, $upload, $serviceTypes, &$saved, &$errors) {
                foreach ($rows as $row) {
                    $serviceTypeId = $serviceTypes[$row['service_type']] ?? null;

                    if (! $serviceTypeId) {
                        $errors[] = [
                            'row'    => $row['row_number'],
                            'reason' => "نوع خدمت «{$row['service_type']}» نامعتبر است",
                        ];
                        continue;
                    }

                    Performance::create([
                        'date'              => $row['date'],
                        'branch_code'       => $upload->branch_code,
                        'personnel_code'    => $row['personnel_code'],
                        'service_type_id'   => $serviceTypeId,
                        'customer_account'  => $row['customer_account'],
                        'customer_name'     => $row['customer_name'],
                        'terminal_number'   => $row['terminal_number'] ?: null,
                        'colleague_account' => $row['colleague_account'] ?: null,
                        'notes'             => $row['notes'] ?: null,
                        'upload_id'         => $upload->id,
                        'validation_status_id' => 1, // در انتظار بررسی
                    ]);

                    $saved++;
                }
            });

            $upload->markAsCompleted($saved, count($errors), $errors);

        } catch (InvalidExcelStructureException $e) {
            $upload->markAsFailed($e->getMessage());
        } catch (\Exception $e) {
            $upload->markAsFailed('خطای داخلی در پردازش فایل');
            Log::error('ProcessUploadedFileJob failed', [
                'upload_id' => $upload->id,
                'error'     => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Upload::find($this->uploadId)?->markAsFailed('پردازش پس از چند تلاش ناموفق بود');
    }
}
