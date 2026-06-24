<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->unsignedInteger('code')->primary();
            $table->string('name', 100);
            $table->unsignedInteger('zone_code')->nullable();
            $table->foreign('zone_code')->references('code')->on('zones')->nullOnDelete();
            $table->enum('grade', [
                'ممتاز الف',
                'ممتاز ب',
                'درجه 1',
                'درجه 2',
                'درجه 3',
                'درجه 4',
                'درجه 5',
            ])->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
