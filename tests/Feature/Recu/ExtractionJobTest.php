<?php

use App\Ai\Agents\ExtracteurDepenses;
use App\Enums\StatutRecu;
use App\Jobs\ExtraireDepensesDuRecu;
use App\Models\Recu;
use App\Models\User;
use Laravel\Ai\Ai;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('processes extraction successfully with fake AI result', function () {
    $recu = Recu::factory()->create([
        'user_id' => $this->user->id,
        'statut' => StatutRecu::EnAttente,
    ]);

    $fakePayload = [
        'articles' => [
            ['libelle' => 'Lait entier', 'quantite' => 3, 'prix_unitaire' => 15.00, 'categorie' => 'alimentaire'],
            ['libelle' => 'Pain complet', 'quantite' => 2, 'prix_unitaire' => 5.00, 'categorie' => 'alimentaire'],
        ],
        'total_estime' => 55.00,
        'devise' => 'MAD',
    ];

    Ai::fakeAgent(ExtracteurDepenses::class, [$fakePayload]);

    $job = new ExtraireDepensesDuRecu($recu);
    $job->handle();

    $recu->refresh();

    expect($recu->statut)->toBe(StatutRecu::Traite);
    expect($recu->payload_ia)->toEqual($fakePayload);

    assertDatabaseCount('depenses', 2);
    assertDatabaseHas('depenses', [
        'recu_id' => $recu->id,
        'libelle' => 'Lait entier',
        'quantite' => 3,
        'prix_unitaire' => 15.00,
        'categorie' => 'alimentaire',
    ]);
});

it('sets receipt to echoue on extraction failure', function () {
    $recu = Recu::factory()->create([
        'user_id' => $this->user->id,
        'statut' => StatutRecu::EnAttente,
    ]);

    Ai::fakeAgent(ExtracteurDepenses::class, function () {
        throw new \Exception('API error: rate limit exceeded');
    });

    $job = new ExtraireDepensesDuRecu($recu);

    try {
        $job->handle();
    } catch (\Exception $e) {
        // expected
    }

    $recu->refresh();

    expect($recu->statut)->toBe(StatutRecu::Echoue);
    expect($recu->message_erreur)->toContain('API error');
});

it('skips already processed receipts', function () {
    $recu = Recu::factory()->create([
        'user_id' => $this->user->id,
        'statut' => StatutRecu::Traite,
    ]);

    $job = new ExtraireDepensesDuRecu($recu);
    $job->handle();

    $recu->refresh();
    expect($recu->statut)->toBe(StatutRecu::Traite);
});

it('replaces expenses on retry for echoue receipt', function () {
    $recu = Recu::factory()->create([
        'user_id' => $this->user->id,
        'statut' => StatutRecu::Echoue,
    ]);

    $recu->depenses()->create([
        'libelle' => 'Old item',
        'quantite' => 1,
        'prix_unitaire' => 10.00,
        'categorie' => 'autre',
    ]);

    $fakePayload = [
        'articles' => [
            ['libelle' => 'Nouvel article', 'quantite' => 2, 'prix_unitaire' => 25.00, 'categorie' => 'alimentaire'],
        ],
        'total_estime' => 50.00,
        'devise' => 'MAD',
    ];

    Ai::fakeAgent(ExtracteurDepenses::class, [$fakePayload]);

    $job = new ExtraireDepensesDuRecu($recu);
    $job->handle();

    $recu->refresh();

    expect($recu->statut)->toBe(StatutRecu::Traite);
    expect($recu->depenses()->count())->toBe(1);
    expect($recu->depenses()->first()->libelle)->toBe('Nouvel article');
});
