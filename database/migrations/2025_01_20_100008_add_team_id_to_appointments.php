<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meetings_appointments')) {
            Schema::table('meetings_appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('meetings_appointments', 'team_id')) {
                    $table->foreignId('team_id')->nullable()->after('user_id')->constrained('teams')->nullOnDelete();
                    $table->index('team_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('meetings_appointments')) {
            Schema::table('meetings_appointments', function (Blueprint $table) {
                if (Schema::hasColumn('meetings_appointments', 'team_id')) {
                    $table->dropForeign(['team_id']);
                    $table->dropColumn('team_id');
                }
            });
        }
    }
};

