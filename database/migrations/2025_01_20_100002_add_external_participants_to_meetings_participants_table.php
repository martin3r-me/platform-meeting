<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings_participants', function (Blueprint $table) {
            // Unique Constraint entfernen (wird später neu erstellt)
            $table->dropUnique(['meeting_id', 'user_id']);
            
            // Foreign Key Constraint entfernen, damit wir user_id nullable machen können
            $table->dropForeign(['user_id']);
            
            // user_id nullable machen, damit externe Teilnehmer ohne User-Account gespeichert werden können
            $table->foreignId('user_id')->nullable()->change();
            
            // Foreign Key Constraint wieder hinzufügen (mit onDelete cascade)
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            
            // Felder für externe Teilnehmer hinzufügen
            $table->string('email')->nullable()->after('user_id');
            $table->string('name')->nullable()->after('email');
            
            // Index für Email-Suche
            $table->index('email');
            
            // Unique Constraint: Entweder user_id ODER (meeting_id + email) muss eindeutig sein
            // Wir verwenden einen zusammengesetzten Unique Index für meeting_id + email
            // und behalten den bestehenden für meeting_id + user_id
            $table->unique(['meeting_id', 'user_id'], 'meetings_participants_meeting_user_unique');
            $table->unique(['meeting_id', 'email'], 'meetings_participants_meeting_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('meetings_participants', function (Blueprint $table) {
            // Unique Constraints entfernen
            $table->dropUnique('meetings_participants_meeting_email_unique');
            $table->dropUnique('meetings_participants_meeting_user_unique');
            
            // Foreign Key Constraint entfernen
            $table->dropForeign(['user_id']);
            
            // Spalten entfernen
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'name']);
            
            // user_id wieder required machen (kann Datenverlust verursachen, wenn externe Teilnehmer existieren)
            $table->foreignId('user_id')->nullable(false)->change();
            
            // Foreign Key und Unique Constraint wiederherstellen
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['meeting_id', 'user_id']);
        });
    }
};

