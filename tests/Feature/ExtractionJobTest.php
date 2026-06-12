<?php

use App\Ai\Agents\ExtracteurDepenses;
use App\Enums\StatutRecu;
use App\Models\User;

beforeEach(function () {
    ExtracteurDepenses::fake();
});

test('successful extraction creates typed depenses and marks receipt as traite', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => '2kg Farine à 5dh, 1L Huile à 20dh',
        'statut' => StatutRecu::EnAttente,
    ]);

    ExtracteurDepenses::fake([
        fn ($prompt, $attachments, $provider, $model) => [
            'articles' => [
                ['libelle' => 'Farine', 'quantite' => 2, 'prix_unitaire' => 5.0, 'categorie' => 'alimentaire'],
                ['libelle' => 'Huile', 'quantite' => 1, 'prix_unitaire' => 20.0, 'categorie' => 'alimentaire'],
            ],
            'total_estime' => 30.0,
            'devise' => 'MAD',
        ],
    ]);

    (new \App\Jobs\ExtraireDepensesDuRecu($recu))->handle();

    $this->assertDatabaseHas('recus', [
        'id' => $recu->id,
        'statut' => StatutRecu::Traite->value,
    ]);

    $this->assertDatabaseHas('depenses', [
        'recu_id' => $recu->id,
        'libelle' => 'Farine',
        'quantite' => 2,
        'prix_unitaire' => 5.0,
        'categorie' => 'alimentaire',
    ]);

    $this->assertDatabaseHas('depenses', [
        'recu_id' => $recu->id,
        'libelle' => 'Huile',
        'quantite' => 1,
        'prix_unitaire' => 20.0,
        'categorie' => 'alimentaire',
    ]);

    $recu->refresh();
    expect($recu->payload_ia)->not->toBeNull();
    expect($recu->payload_ia['total_estime'])->toEqual(30);
});

test('extraction failure sets receipt to echoue with error message', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => '2kg Farine à 5dh',
        'statut' => StatutRecu::EnAttente,
    ]);

    ExtracteurDepenses::fake(fn () => throw new \Exception('API Error'));

    (new \App\Jobs\ExtraireDepensesDuRecu($recu))->handle();

    $this->assertDatabaseHas('recus', [
        'id' => $recu->id,
        'statut' => StatutRecu::Echoue->value,
    ]);

    $recu->refresh();
    expect($recu->message_erreur)->toContain('API Error');
    expect($recu->depenses)->toBeEmpty();
});

test('already processed receipt is skipped', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => '2kg Farine à 5dh',
        'statut' => StatutRecu::Traite,
    ]);

    ExtracteurDepenses::fake([
        fn ($prompt, $attachments, $provider, $model) => [
            'articles' => [
                ['libelle' => 'Farine', 'quantite' => 2, 'prix_unitaire' => 5.0, 'categorie' => 'alimentaire'],
            ],
            'total_estime' => 10.0,
            'devise' => 'MAD',
        ],
    ]);

    (new \App\Jobs\ExtraireDepensesDuRecu($recu))->handle();

    $recu->refresh();
    expect($recu->statut)->toBe(StatutRecu::Traite);
});
