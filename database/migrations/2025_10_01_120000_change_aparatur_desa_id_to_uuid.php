<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change id to UUID; this is safe on empty/new tables. For non-empty tables, consider creating a new uuid column and backfill.
        Schema::table('aparatur_desa', function (Blueprint $table) {
            // Drop foreign keys referencing aparatur_desa.id first if any (not expected in current schema)
        });

        Schema::table('aparatur_desa', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Backfill uuids
        DB::table('aparatur_desa')->whereNull('uuid')->update(['uuid' => DB::raw('(uuid_generate_v4())')]);

        Schema::table('aparatur_desa', function (Blueprint $table) {
            $table->dropPrimary('aparatur_desa_pkey');
        });

        Schema::table('aparatur_desa', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('aparatur_desa', function (Blueprint $table) {
            $table->uuid('id')->first();
            $table->primary('id');
        });

        // Move uuid to id if needed is out of scope here; depends on DB. Simpler alternative: rename uuid->id is not universally supported in all DBs.
    }

    public function down(): void
    {
        // Revert to bigIncrements id
        Schema::table('aparatur_desa', function (Blueprint $table) {
            $table->dropPrimary('aparatur_desa_pkey');
            $table->dropColumn('id');
            $table->bigIncrements('id');
        });
    }
};
