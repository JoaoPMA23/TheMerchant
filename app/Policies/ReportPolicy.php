<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function moderate(User $user, Report $report): bool
    {
        return $user->isAdmin() && in_array($report->status, ['aberta', 'em_analise'], true);
    }

    /**
     * RF19: o bloqueio de uma denúncia procedente pode ser revertido por um administrador.
     */
    public function restoreListing(User $user, Report $report): bool
    {
        $listing = $report->listing;

        return $user->isAdmin()
            && $report->status === 'procedente'
            && $listing !== null
            && $listing->status === 'bloqueado'
            && $listing->archived_at === null;
    }
}
