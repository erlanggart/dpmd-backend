<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satlinmas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade');
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->enum('status_kelembagaan', ['aktif', 'nonaktif'])->default('aktif');
            $table->enum('status_verifikasi', ['verified', 'unverified'])->default('unverified');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satlinmas');
    }
};
