<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aparatur_desa', function (Blueprint $table) {
            if (Schema::hasColumn('aparatur_desa', 'niap')) {
                $table->dropColumn('niap');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aparatur_desa', function (Blueprint $table) {
            $table->string('niap')->nullable()->comment('Nomor Induk Aparatur Pemerintah');
        });
    }
};
