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
        Schema::table('bumdes', function (Blueprint $table) {
            // Tambahkan foreign key untuk PERDES dan SK BUMDES yang terkait dengan produk hukum desa
            $table->foreignUuid('produk_hukum_perdes_id')
                  ->nullable()
                  ->after('Perdes')
                  ->constrained('produk_hukums')
                  ->nullOnDelete()
                  ->comment('Foreign key ke tabel produk_hukums untuk PERDES tentang BUMDES');
                  
            $table->foreignUuid('produk_hukum_sk_bumdes_id')
                  ->nullable()
                  ->after('SK_BUM_Desa')
                  ->constrained('produk_hukums')
                  ->nullOnDelete()
                  ->comment('Foreign key ke tabel produk_hukums untuk SK BUMDES');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bumdes', function (Blueprint $table) {
            $table->dropForeign(['produk_hukum_perdes_id']);
            $table->dropForeign(['produk_hukum_sk_bumdes_id']);
            $table->dropColumn(['produk_hukum_perdes_id', 'produk_hukum_sk_bumdes_id']);
        });
    }
};
