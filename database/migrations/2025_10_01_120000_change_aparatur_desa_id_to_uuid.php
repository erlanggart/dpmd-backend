<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: superseded by 2025_10_01_120100_add_uuid_to_aparatur_desa_table
        // This migration attempted DB-specific operations; keeping it empty to avoid failures.
    }

    public function down(): void
    {
        // No-op
    }
};
