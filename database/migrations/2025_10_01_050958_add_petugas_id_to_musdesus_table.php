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
        Schema::table('musdesus', function (Blueprint $table) {
            $table->unsignedBigInteger('petugas_id')->nullable()->after('kecamatan_id');
            $table->foreign('petugas_id')->references('id')->on('petugas_monitoring')->onDelete('set null');
            $table->index('petugas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('musdesus', function (Blueprint $table) {
            $table->dropForeign(['petugas_id']);
            $table->dropIndex(['petugas_id']);
            $table->dropColumn('petugas_id');
        });
    }
};
