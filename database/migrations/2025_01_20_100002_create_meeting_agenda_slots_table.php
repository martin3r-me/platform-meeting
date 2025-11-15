<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meetings_agenda_slots')) {
            return;
        }

        Schema::create('meetings_agenda_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings_meetings')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->boolean('is_done_slot')->default(false);
            $table->timestamps();
            
            $table->index(['meeting_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings_agenda_slots');
    }
};

