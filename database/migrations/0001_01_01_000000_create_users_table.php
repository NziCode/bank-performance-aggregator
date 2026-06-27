<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('personnel_code')->primary();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('national_code', 10)->unique();
            $table->string('position', 100)->nullable();
            $table->string('mobile', 11)->nullable();
            $table->enum('education', [
                'زیر دیپلم',
                'دیپلم',
                'فوق دیپلم',
                'لیسانس',
                'فوق لیسانس',
                'دکترا',
            ])->nullable();
            $table->enum('gender', ['آقا', 'خانم'])->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->enum('workplace_type', ['branch', 'zone', 'branch_office', 'staff'])->nullable();
            $table->unsignedInteger('branch_code')->nullable();
            $table->unsignedInteger('zone_code')->nullable();
            $table->unsignedBigInteger('branch_office_id')->nullable();
            $table->unsignedInteger('staff_unit_code')->nullable();

            $table->foreign('branch_code')->references('code')->on('branches')->nullOnDelete();
            $table->foreign('zone_code')->references('code')->on('zones')->nullOnDelete();
            $table->foreign('branch_office_id')->references('id')->on('branch_offices')->nullOnDelete();
            $table->foreign('staff_unit_code')->references('code')->on('staff_units')->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
