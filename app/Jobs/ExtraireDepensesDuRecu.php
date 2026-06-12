<?php

namespace App\Jobs;

use App\Ai\Agents\ExtracteurDepenses;
use App\Enums\StatutRecu;
use App\Models\Recu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ExtraireDepensesDuRecu implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public Recu $recu,
    ) {}

    public function handle(): void
    {
        if ($this->recu->statut === StatutRecu::Traite) {
            return;
        }

        try {
            $agent = new ExtracteurDepenses;
            $response = $agent->prompt($this->recu->texte_brut);

            $structured = $response->structured;

            DB::transaction(function () use ($structured) {
                $this->recu->update([
                    'payload_ia' => $structured,
                ]);

                $this->recu->depenses()->delete();

                foreach ($structured['articles'] as $article) {
                    $this->recu->depenses()->create([
                        'libelle' => $article['libelle'],
                        'quantite' => $article['quantite'],
                        'prix_unitaire' => $article['prix_unitaire'],
                        'categorie' => $article['categorie'],
                    ]);
                }

                $this->recu->update([
                    'statut' => StatutRecu::Traite,
                ]);
            });
        } catch (\Throwable $e) {
            $this->recu->update([
                'statut' => StatutRecu::Echoue,
                'message_erreur' => 'L\'extraction des dépenses a échoué : ' . $e->getMessage(),
            ]);
        }
    }
}
