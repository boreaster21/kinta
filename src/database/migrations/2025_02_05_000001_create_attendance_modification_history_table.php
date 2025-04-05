<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_modification_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('modified_by')->constrained('users');
            $table->enum('modification_type', ['direct', 'request'])->comment('direct: 管理者による直接修正, request: 修正申請による修正');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->string('total_break_time', 5)->nullable();
            $table->string('total_work_time', 5)->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['attendance_id', 'created_at']);
            $table->index('modified_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_modification_history');
    }
}; 