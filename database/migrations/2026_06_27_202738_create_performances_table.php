<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('تاریخ ثبت عملکرد به میلادی');
            $table->unsignedInteger('branch_code')->comment('شعبه در زمان ثبت');
            $table->foreign('branch_code')->references('code')->on('branches');
            $table->string('personnel_code', 20)->comment('شماره کارمندی');
            $table->foreign('personnel_code')->references('personnel_code')->on('users');
            $table->unsignedBigInteger('service_type_id');
            $table->foreign('service_type_id')->references('id')->on('service_types');
            $table->string('customer_account', 20)->comment('شماره حساب مشتری');
            $table->string('customer_name', 100)->comment('نام مشتری');
            $table->string('terminal_number', 50)->nullable()->comment('شماره پایانه — فقط پایش پایانه');
            $table->string('colleague_account', 20)->nullable()->comment('شماره حساب همکار — فقط پایش پایانه');
            $table->text('notes')->nullable()->comment('توضیحات تکمیلی');
            $table->unsignedBigInteger('upload_id')->nullable()->comment('شناسه فایل Excel');

            // اعتبارسنجی
            $table->unsignedBigInteger('validation_status_id')->default(1);
            $table->foreign('validation_status_id')->references('id')->on('validation_statuses');
            $table->unsignedBigInteger('rejection_reason_id')->nullable();
            $table->foreign('rejection_reason_id')->references('id')->on('rejection_reasons');
            $table->string('validated_by', 20)->nullable();
            $table->foreign('validated_by')->references('personnel_code')->on('users');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index('date');
            $table->index('branch_code');
            $table->index('personnel_code');
            $table->index('validation_status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};
