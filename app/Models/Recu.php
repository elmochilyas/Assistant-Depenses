<?php

namespace App\Models;

use App\Enums\StatutRecu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recu extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'texte_brut',
        'statut',
        'payload_ia',
        'message_erreur',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutRecu::class,
            'payload_ia' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }
}
