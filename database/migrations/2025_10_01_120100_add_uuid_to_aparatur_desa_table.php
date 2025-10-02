<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aparatur_desa', function (Blueprint $table) {
            if (!Schema::hasColumn('aparatur_desa', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        // Backfill UUIDs at application layer if DB function not available.
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'aparatur_desa';
            public $timestamps = false;
            protected $guarded = [];
        };

        $model->newQuery()->whereNull('uuid')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $row->uuid = (string) \Illuminate\Support\Str::uuid();
                $row->save();
            }
        }, 'id');
    }

    public function down(): void
    {
        Schema::table('aparatur_desa', function (Blueprint $table) {
            if (Schema::hasColumn('aparatur_desa', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
