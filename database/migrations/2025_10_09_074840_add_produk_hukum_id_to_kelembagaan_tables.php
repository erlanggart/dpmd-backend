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
        // Add produk_hukum_id to all kelembagaan tables for SK pembentukan lembaga

        Schema::table('rws', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });

        Schema::table('rts', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });

        Schema::table('posyandus', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });

        Schema::table('karang_tarunas', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });

        Schema::table('lpms', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });

        Schema::table('pkks', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });

        Schema::table('satlinmas', function (Blueprint $table) {
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->after('status_verifikasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rws', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });

        Schema::table('rts', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });

        Schema::table('posyandus', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });

        Schema::table('karang_tarunas', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });

        Schema::table('lpms', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });

        Schema::table('pkks', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });

        Schema::table('satlinmas', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_id']);
            $table->dropColumn('produk_hukum_id');
        });
    }
};
