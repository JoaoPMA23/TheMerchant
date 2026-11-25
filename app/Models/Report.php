<?php

namespace App\Models;

use App\Support\StatusLabel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = [
        'reporter_id',
        'listing_id',
        'reason',
        'details',
        'status', // 'aberta', 'em_analise', 'procedente', 'improcedente'
        'moderator_id',
        'resolution_notes',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ModerationAudit::class)->latest();
    }

    /** Rótulo em português do status, para exibir nas telas. */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => StatusLabel::for($this->status));
    }
}
