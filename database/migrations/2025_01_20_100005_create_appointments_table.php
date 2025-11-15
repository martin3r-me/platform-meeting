<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Microsoft Graph API
            $table->string('microsoft_event_id')->nullable();
            $table->string('sync_status')->default('pending'); // synced, pending, error
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            
            $table->timestamps();
            
            $table->unique(['meeting_id', 'user_id']);
            $table->index('user_id');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

