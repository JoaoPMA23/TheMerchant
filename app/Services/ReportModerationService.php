<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ModerationAudit;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportModerationService
{
    /**
     * @param  array{status: string, resolution_notes: string, block_listing?: bool}  $data
     */
    public function moderate(User $moderator, Report $report, array $data): Report
    {
        return DB::transaction(function () use ($moderator, $report, $data): Report {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);

            if (! in_array($lockedReport->status, ['aberta', 'em_analise'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Esta denúncia já foi encerrada e não pode ser moderada novamente.',
                ]);
            }

            $previousReportStatus = $lockedReport->status;
            $listing = $lockedReport->listing;
            $previousListingStatus = $listing?->status;
            $shouldBlockListing = $data['status'] === 'procedente'
                && (bool) ($data['block_listing'] ?? false)
                && $listing !== null;

            $lockedReport->update([
                'status' => $data['status'],
                'resolution_notes' => $data['resolution_notes'],
                'moderator_id' => $moderator->id,
            ]);

            if ($shouldBlockListing) {
                $listing->update(['status' => 'bloqueado']);
            }

            ModerationAudit::create([
                'report_id' => $lockedReport->id,
                'moderator_id' => $moderator->id,
                'listing_id' => $listing?->id,
                'action' => $shouldBlockListing ? 'denuncia_julgada_anuncio_bloqueado' : 'denuncia_moderada',
                'previous_report_status' => $previousReportStatus,
                'new_report_status' => $lockedReport->status,
                'previous_listing_status' => $previousListingStatus,
                'new_listing_status' => $listing?->fresh()?->status,
                'resolution_notes' => $data['resolution_notes'],
            ]);

            return $lockedReport->fresh(['reporter', 'listing', 'moderator']);
        });
    }

    /**
     * Desfaz o bloqueio sem republicar: o anúncio volta pausado (ou rascunho, se era rascunho)
     * e o vendedor o reativa pelo fluxo normal, que revalida jogo e categoria.
     */
    public function restoreListing(User $moderator, Report $report, string $notes): Listing
    {
        return DB::transaction(function () use ($moderator, $report, $notes): Listing {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);
            $listing = $lockedReport->listing_id === null
                ? null
                : Listing::query()->lockForUpdate()->find($lockedReport->listing_id);

            if ($lockedReport->status !== 'procedente'
                || $listing === null
                || $listing->status !== 'bloqueado'
                || $listing->archived_at !== null) {
                throw ValidationException::withMessages([
                    'resolution_notes' => 'Este anúncio não está bloqueado e não pode ser restaurado.',
                ]);
            }

            $statusBeforeBlock = ModerationAudit::query()
                ->where('listing_id', $listing->id)
                ->where('new_listing_status', 'bloqueado')
                ->where('previous_listing_status', '!=', 'bloqueado')
                ->latest('id')
                ->value('previous_listing_status');
            $restoredStatus = $statusBeforeBlock === 'rascunho' ? 'rascunho' : 'pausado';

            $listing->update(['status' => $restoredStatus]);

            ModerationAudit::create([
                'report_id' => $lockedReport->id,
                'moderator_id' => $moderator->id,
                'listing_id' => $listing->id,
                'action' => 'anuncio_restaurado',
                'previous_report_status' => $lockedReport->status,
                'new_report_status' => $lockedReport->status,
                'previous_listing_status' => 'bloqueado',
                'new_listing_status' => $restoredStatus,
                'resolution_notes' => $notes,
            ]);

            return $listing->fresh();
        });
    }
}
