<?php

namespace App\Jobs;

use App\Exceptions\InvalidExcelStructureException;
use App\Models\Performance;
use App\Models\ServiceType;
use App\Models\Upload;
use App\Models\User;
use App\Services\Excel\ExcelParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;

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
            $parsed = $parser->parse(Storage::path($upload->stored_path));

            $serviceTypes = ServiceType::pluck('id', 'name');
            $errors       = [];
            $saved        = 0;
            $userCache    = []; // personnel_code => User|null

            DB::transaction(function () use ($parsed, $upload, $serviceTypes, &$saved, &$errors, &$userCache) {

                // پردازش شیت خدمات نوین
                foreach ($parsed['banking_service'] as $row) {
                    $serviceTypeId = $serviceTypes[$row['service_type']] ?? null;

                    if (! $serviceTypeId) {
                        $errors[] = [
                            'row'    => $row['row_number'],
                            'sheet'  => 'خدمات نوین',
                            'reason' => "نوع خدمت «{$row['service_type']}» نامعتبر است",
                        ];
                        continue;
                    }

                    $user = $this->resolveUser($row['personnel_code'], $userCache);
                    if (! $user) {
                        $errors[] = [
                            'row'    => $row['row_number'],
                            'sheet'  => 'خدمات نوین',
                            'reason' => "کد پرسنلی «{$row['personnel_code']}» در سیستم یافت نشد",
                        ];
                        continue;
                    }

                    $date = $this->parseDate($row['date']);
                    if (! $date) {
                        $errors[] = [
                            'row'    => $row['row_number'],
                            'sheet'  => 'خدمات نوین',
                            'reason' => "تاریخ «{$row['date']}» نامعتبر است",
                        ];
                        continue;
                    }

                    Performance::create(array_merge(
                        [
                            'date'                 => $date,
                            'personnel_code'       => $row['personnel_code'],
                            'service_type_id'      => $serviceTypeId,
                            'customer_account'     => $row['customer_account'],
                            'customer_name'        => $row['customer_name'],
                            'notes'                => $row['notes'] ?: null,
                            'upload_id'            => $upload->id,
                            'validation_status_id' => 1,
                        ],
                        $this->snapshotWorkplace($user)
                    ));

                    $saved++;
                }

                // پردازش شیت پایش پایانه
                $posServiceId = $serviceTypes['پایش پایانه فروش'] ?? null;

                foreach ($parsed['pos_monitoring'] as $row) {
                    $user = $this->resolveUser($row['personnel_code'], $userCache);
                    if (! $user) {
                        $errors[] = [
                            'row'    => $row['row_number'],
                            'sheet'  => 'پایش پایانه های فروش',
                            'reason' => "کد پرسنلی «{$row['personnel_code']}» در سیستم یافت نشد",
                        ];
                        continue;
                    }

                    $date = $this->parseDate($row['date']);
                    if (! $date) {
                        $errors[] = [
                            'row'    => $row['row_number'],
                            'sheet'  => 'پایش پایانه های فروش',
                            'reason' => "تاریخ «{$row['date']}» نامعتبر است",
                        ];
                        continue;
                    }

                    Performance::create(array_merge(
                        [
                            'date'                 => $date,
                            'personnel_code'       => $row['personnel_code'],
                            'service_type_id'      => $posServiceId,
                            'customer_account'     => $row['customer_account'],
                            'customer_name'        => $row['customer_name'],
                            'terminal_number'      => $row['terminal_number'] ?: null,
                            'colleague_account'    => $row['colleague_account'] ?: null,
                            'notes'                => $row['notes'] ?: null,
                            'upload_id'            => $upload->id,
                            'validation_status_id' => 1,
                        ],
                        $this->snapshotWorkplace($user)
                    ));

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

    /**
     * فقط کاربر را بر اساس کد پرسنلی پیدا می‌کند (یا null در صورت نامعتبر بودن).
     */
    private function resolveUser(string $personnelCode, array &$cache): ?User
    {
        if (array_key_exists($personnelCode, $cache)) {
            return $cache[$personnelCode];
        }

        return $cache[$personnelCode] = User::where('personnel_code', $personnelCode)->first();
    }

    /**
     * محل خدمت فعلی کارمند را در لحظه ثبت عملکرد snapshot می‌گیرد.
     * این مقادیر دیگر هیچ‌گاه تغییر نمی‌کنند، حتی اگر کارمند بعداً جابجا شود —
     * در نتیجه گزارش‌های گذشته همیشه به محل خدمت واقعی آن زمان اشاره می‌کنند.
     */
    private function snapshotWorkplace(User $user): array
    {
        return [
            'workplace_type'   => $user->workplace_type,
            'branch_code'      => $user->workplace_type === 'branch' ? $user->branch_code : null,
            'zone_code'        => $user->workplace_type === 'zone' ? $user->zone_code : null,
            'branch_office_id' => $user->workplace_type === 'branch_office' ? $user->branch_office_id : null,
            'staff_unit_code'  => $user->workplace_type === 'staff' ? $user->staff_unit_code : null,
        ];
    }

    private function parseDate(string $date): ?string
    {
        $date = trim($date);
        if (empty($date)) return null;

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $m)) {
            try {
                $jalali = Jalalian::fromFormat('Y/m/d', "{$m[1]}/{$m[2]}/{$m[3]}");
                return $jalali->toCarbon()->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public function failed(\Throwable $e): void
    {
        Upload::find($this->uploadId)?->markAsFailed('پردازش پس از چند تلاش ناموفق بود');
    }
}
