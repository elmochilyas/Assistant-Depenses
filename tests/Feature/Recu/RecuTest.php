<?php

use App\Enums\CategorieDepense;
use App\Enums\StatutRecu;
use App\Jobs\ExtraireDepensesDuRecu;
use App\Models\Recu;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('creates a receipt and dispatches job when valid text is submitted', function () {
    Queue::fake();

    get(route('recus.create'))->assertOk();

    $response = $this->post(route('recus.store'), [
        'texte_brut' => 'Facture fournisseur - Lait 3x15dh, Pain 2x5dh, Huile 1x40dh',
    ]);

    $response->assertRedirect(route('recus.index'));
    $response->assertSessionHas('success', 'Reçu en cours de traitement');

    assertDatabaseHas('recus', [
        'user_id' => $this->user->id,
        'statut' => StatutRecu::EnAttente->value,
    ]);

    Queue::assertPushed(ExtraireDepensesDuRecu::class);
});

it('rejects empty receipt text and does not dispatch job', function () {
    Queue::fake();

    $response = $this->post(route('recus.store'), [
        'texte_brut' => '',
    ]);

    $response->assertSessionHasErrors('texte_brut');

    Queue::assertNotPushed(ExtraireDepensesDuRecu::class);
});

it('rejects receipt text below minimum length', function () {
    Queue::fake();

    $response = $this->post(route('recus.store'), [
        'texte_brut' => 'Court',
    ]);

    $response->assertSessionHasErrors('texte_brut');

    Queue::assertNotPushed(ExtraireDepensesDuRecu::class);
});

it('redirects unauthenticated user to login', function () {
    auth()->logout();

    get(route('recus.create'))->assertRedirect(route('login'));
    get(route('recus.index'))->assertRedirect(route('login'));
});

it('shows own receipts list with status and expense count', function () {
    $recu = Recu::factory()->create([
        'user_id' => $this->user->id,
        'statut' => StatutRecu::Traite,
    ]);

    $depense = $recu->depenses()->create([
        'libelle' => 'Lait',
        'quantite' => 3,
        'prix_unitaire' => 15.00,
        'categorie' => CategorieDepense::Alimentaire,
    ]);

    get(route('recus.index'))
        ->assertOk()
        ->assertSee('Traité')
        ->assertSee('1');
});

it('prevents user from viewing another user receipt', function () {
    $otherUser = User::factory()->create();
    $recu = Recu::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    get(route('recus.show', $recu))->assertForbidden();
});

it('prevents user from deleting another user receipt', function () {
    $otherUser = User::factory()->create();
    $recu = Recu::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->delete(route('recus.destroy', $recu))->assertForbidden();

    assertDatabaseHas('recus', ['id' => $recu->id]);
});

it('deletes receipt with cascading expenses', function () {
    $recu = Recu::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $depense = $recu->depenses()->create([
        'libelle' => 'Pain',
        'quantite' => 2,
        'prix_unitaire' => 5.00,
        'categorie' => CategorieDepense::Alimentaire,
    ]);

    $this->delete(route('recus.destroy', $recu));

    assertDatabaseMissing('recus', ['id' => $recu->id]);
    assertDatabaseMissing('depenses', ['id' => $depense->id]);
});
