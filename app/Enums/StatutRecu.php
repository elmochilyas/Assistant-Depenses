<?php

namespace App\Enums;

enum StatutRecu: string
{
    case EnAttente = 'en_attente';
    case Traite = 'traite';
    case Echoue = 'echoue';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Traite => 'Traité',
            self::Echoue => 'Échoué',
        };
    }
}
