<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('stored_path', 500);
            $table->unsignedInteger('branch_code')->nullable();
            $table->foreign('branch_code')->references('code')->on('branches')->nullOnDelete();
            $table->date('period')->nullable()->comment('دوره گزارش');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_rejected')->default(0);
            $table->json('errors')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('uploaded_by', 20)->nullable();
            $table->foreign('uploaded_by')->references('personnel_code')->on('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('period');
            $table->index('branch_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
