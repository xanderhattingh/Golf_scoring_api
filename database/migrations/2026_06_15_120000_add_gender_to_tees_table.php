<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('tees', 'gender')) {
            Schema::table('tees', function (Blueprint $table) {
                // 'M' | 'F' | null (unknown) — lets the UI flag the right tee per player
                $table->char('gender', 1)->nullable()->after('description');
            });
        }

        // Best-effort backfill from the existing description text.
        // Check ladies/women first because "women" contains "men".
        DB::table('tees')
            ->whereNull('gender')
            ->where(function ($q) {
                $q->whereRaw('LOWER(description) LIKE ?', ['%ladies%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%women%']);
            })
            ->update(['gender' => 'F']);

        DB::table('tees')
            ->whereNull('gender')
            ->where(function ($q) {
                $q->whereRaw('LOWER(description) LIKE ?', ['%men%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%senior%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%pro%']);
            })
            ->update(['gender' => 'M']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tees', 'gender')) {
            Schema::table('tees', function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }
    }
};
