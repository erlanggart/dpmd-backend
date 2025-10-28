<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hero_galleries', function (Blueprint $table) {
            $table->id();
            $table->string('image_path'); // Untuk menyimpan path file gambar
            $table->string('title')->nullable(); // Untuk alt text
            $table->boolean('is_active')->default(true); // Status aktif/tidak aktif
            $table->integer('order')->default(0); // Urutan tampil
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_galleries');
    }
};
