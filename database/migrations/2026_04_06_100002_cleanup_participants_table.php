<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_participants', function (Blueprint $table) {
            $columns = [];
            $allColumns = Schema::getColumnListing('meetings_participants');

            foreach (['microsoft_attendee_id', 'response_status', 'response_time'] as $col) {
                if (in_array($col, $allColumns)) {
                    $columns[] = $col;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings_participants', function (Blueprint $table) {
            $table->string('microsoft_attendee_id')->nullable();
            $table->string('response_status')->nullable();
            $table->dateTime('response_time')->nullable();
        });
    }
};
