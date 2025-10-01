<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_hukums', function (Blueprint $table) {
            if (!Schema::hasColumn('produk_hukums', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'produk_hukums';
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
        Schema::table('produk_hukums', function (Blueprint $table) {
            if (Schema::hasColumn('produk_hukums', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
