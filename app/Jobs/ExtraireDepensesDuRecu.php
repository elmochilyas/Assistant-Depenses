<?php

namespace App\Jobs;

use App\Ai\Agents\ExtracteurDepenses;
use App\Enums\StatutRecu;
use App\Models\Recu;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExtraireDepensesDuRecu implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Recu $recu
    ) {}

    public function handle(): void
    {
        $this->recu->load('user');

        if ($this->recu->statut === StatutRecu::Traite) {
            Log::info('Receipt already processed, skipping', ['recu_id' => $this->recu->id]);
            return;
        }

        try {
            $agent = ExtracteurDepenses::make();
            $response = $agent->prompt($this->recu->texte_brut);
            $payload = $response->toArray();

            DB::transaction(function () use ($payload) {
                $this->recu->update([
                    'payload_ia' => $payload,
                    'message_erreur' => null,
                ]);

                $this->recu->depenses()->delete();

                foreach ($payload['articles'] as $article) {
                    $this->recu->depenses()->create([
                        'libelle' => $article['libelle'],
                        'quantite' => $article['quantite'],
                        'prix_unitaire' => $article['prix_unitaire'],
                        'categorie' => $article['categorie'],
                    ]);
                }

                $this->recu->update(['statut' => StatutRecu::Traite]);
            });

            Log::info('Receipt extraction completed', ['recu_id' => $this->recu->id]);
        } catch (Throwable $e) {
            Log::error('Receipt extraction failed', [
                'recu_id' => $this->recu->id,
                'error' => $e->getMessage(),
            ]);

            $this->recu->update([
                'statut' => StatutRecu::Echoue,
                'message_erreur' => 'Échec de l\'extraction IA : ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        if ($this->recu->statut !== StatutRecu::Traite) {
            $this->recu->update([
                'statut' => StatutRecu::Echoue,
                'message_erreur' => 'Erreur lors du traitement : ' . $e->getMessage(),
            ]);
        }

        Log::error('Receipt extraction job failed', [
            'recu_id' => $this->recu->id,
            'error' => $e->getMessage(),
        ]);
    }
}
