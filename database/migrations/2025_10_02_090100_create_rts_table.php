<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rw_id');
            $table->foreign('rw_id')->references('id')->on('rws')->onDelete('cascade');
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade');
            $table->string('nomor'); // e.g., RT 001
            $table->string('alamat')->nullable();
            $table->enum('status_kelembagaan', ['aktif', 'nonaktif'])->default('aktif');
            $table->enum('status_verifikasi', ['verified', 'unverified'])->default('unverified');
            $table->timestamps();
            $table->unique(['rw_id', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts');
    }
};
