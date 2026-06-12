<?php

use App\Enums\StatutRecu;
use App\Jobs\ExtraireDepensesDuRecu;
use App\Models\Recu;
use App\Models\User;

test('unauthenticated user cannot access receipt pages', function () {
    $this->get(route('recus.index'))->assertRedirect(route('login'));
    $this->get(route('recus.create'))->assertRedirect(route('login'));
    $this->get(route('recus.show', 1))->assertRedirect(route('login'));
});

test('authenticated user can create a receipt and job is dispatched', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recus.store'), [
        'texte_brut' => 'Facture fournisseur: 2kg farine à 5dh, 1L huile à 20dh',
    ]);

    $response->assertRedirect(route('recus.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('recus', [
        'user_id' => $user->id,
        'statut' => StatutRecu::EnAttente->value,
    ]);

    Queue::assertPushed(ExtraireDepensesDuRecu::class);
});

test('empty receipt text is rejected', function () {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recus.store'), [
        'texte_brut' => '',
    ]);

    $response->assertSessionHasErrors('texte_brut');
    Queue::assertNotPushed(ExtraireDepensesDuRecu::class);
});

test('receipt text shorter than minimum is rejected', function () {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recus.store'), [
        'texte_brut' => 'Court',
    ]);

    $response->assertSessionHasErrors('texte_brut');
    Queue::assertNotPushed(ExtraireDepensesDuRecu::class);
});

test('user can view their own receipt', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => StatutRecu::Traite,
    ]);

    $response = $this->actingAs($user)->get(route('recus.show', $recu));

    $response->assertStatus(200);
    $response->assertSee($recu->texte_brut);
});

test('user cannot view another users receipt', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $recu = $user1->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => StatutRecu::Traite,
    ]);

    $this->actingAs($user2)->get(route('recus.show', $recu))->assertForbidden();
});

test('user can delete their own receipt', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => StatutRecu::Traite,
    ]);

    $response = $this->actingAs($user)->delete(route('recus.destroy', $recu));

    $response->assertRedirect(route('recus.index'));
    $this->assertDatabaseMissing('recus', ['id' => $recu->id]);
});

test('user cannot delete another users receipt', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $recu = $user1->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => StatutRecu::Traite,
    ]);

    $this->actingAs($user2)->delete(route('recus.destroy', $recu))->assertForbidden();
});

test('deleting a receipt cascades to its depenses', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => StatutRecu::Traite,
    ]);
    $depense = $recu->depenses()->create([
        'libelle' => 'Pain',
        'quantite' => 2,
        'prix_unitaire' => 1.5,
        'categorie' => 'alimentaire',
    ]);

    $this->actingAs($user)->delete(route('recus.destroy', $recu));

    $this->assertDatabaseMissing('depenses', ['id' => $depense->id]);
});
