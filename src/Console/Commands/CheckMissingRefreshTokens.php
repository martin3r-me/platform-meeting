<?php

namespace Platform\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Platform\Core\Models\User;
use Platform\Core\Models\MicrosoftOAuthToken;

class CheckMissingRefreshTokens extends Command
{
    protected $signature = 'meetings:check-missing-refresh-tokens';
    protected $description = 'Prüft welche User keinen Refresh Token haben und informiert sie';

    public function handle()
    {
        $this->info('Prüfe User ohne Refresh Token...');
        
        $tokensWithoutRefresh = MicrosoftOAuthToken::where(function($query) {
                $query->whereNull('refresh_token')
                      ->orWhere('refresh_token', '');
            })
            ->with('user')
            ->get();
        
        if ($tokensWithoutRefresh->isEmpty()) {
            $this->info('✓ Alle User haben einen Refresh Token.');
            return 0;
        }
        
        $this->warn("⚠️  {$tokensWithoutRefresh->count()} User ohne Refresh Token gefunden:");
        $this->newLine();
        
        $tableData = [];
        foreach ($tokensWithoutRefresh as $token) {
            $user = $token->user;
            $expiresAt = $token->expires_at ? $token->expires_at->format('d.m.Y H:i:s') : 'Unbekannt';
            $isExpired = $token->expires_at && $token->expires_at->isPast();
            
            $tableData[] = [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Abgelaufen' => $isExpired ? '✓ Ja' : '✗ Nein',
                'Läuft ab' => $expiresAt,
            ];
        }
        
        $this->table(['ID', 'Name', 'Email', 'Abgelaufen', 'Läuft ab'], $tableData);
        $this->newLine();
        $this->warn('Diese User müssen sich einmal über Azure SSO neu anmelden, um einen Refresh Token zu erhalten.');
        $this->info('Nach der Anmeldung wird der Refresh Token automatisch gespeichert.');
        
        return 0;
    }
}

