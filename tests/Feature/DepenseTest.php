<?php

use App\Models\User;

test('unauthenticated user cannot access expense list', function () {
    $this->get(route('depenses.index'))->assertRedirect(route('login'));
});

test('authenticated user sees all their expenses without filter', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => 'traite',
    ]);
    $recu->depenses()->createMany([
        ['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire'],
        ['libelle' => 'Eau', 'quantite' => 6, 'prix_unitaire' => 5.0, 'categorie' => 'boissons'],
        ['libelle' => 'Savon', 'quantite' => 1, 'prix_unitaire' => 3.5, 'categorie' => 'hygiene'],
    ]);

    $response = $this->actingAs($user)->get(route('depenses.index'));

    $response->assertStatus(200);
    $response->assertSee('Pain');
    $response->assertSee('Eau');
    $response->assertSee('Savon');
});

test('filter by valid category shows only matching expenses', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => 'traite',
    ]);
    $recu->depenses()->createMany([
        ['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire'],
        ['libelle' => 'Eau', 'quantite' => 6, 'prix_unitaire' => 5.0, 'categorie' => 'boissons'],
    ]);

    $response = $this->actingAs($user)->get(route('depenses.index', ['categorie' => 'alimentaire']));

    $response->assertStatus(200);
    $response->assertSee('Pain');
    $response->assertDontSee('Eau');
});

test('invalid category shows all expenses', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => 'traite',
    ]);
    $recu->depenses()->createMany([
        ['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire'],
        ['libelle' => 'Eau', 'quantite' => 6, 'prix_unitaire' => 5.0, 'categorie' => 'boissons'],
    ]);

    $response = $this->actingAs($user)->get(route('depenses.index', ['categorie' => 'invalid_category']));

    $response->assertStatus(200);
    $response->assertSee('Pain');
    $response->assertSee('Eau');
});

test('category filter respects user data isolation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $recu1 = $user1->recus()->create(['texte_brut' => 'Facture user1 longue', 'statut' => 'traite']);
    $recu1->depenses()->create(['libelle' => 'Pain user1', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire']);

    $recu2 = $user2->recus()->create(['texte_brut' => 'Facture user2 longue', 'statut' => 'traite']);
    $recu2->depenses()->create(['libelle' => 'Pain user2', 'quantite' => 1, 'prix_unitaire' => 3.0, 'categorie' => 'alimentaire']);

    $response = $this->actingAs($user1)->get(route('depenses.index', ['categorie' => 'alimentaire']));

    $response->assertStatus(200);
    $response->assertSee('Pain user1');
    $response->assertDontSee('Pain user2');
});

test('unauthenticated user cannot access category overview', function () {
    $this->get(route('depenses.categories'))->assertRedirect(route('login'));
});

test('category overview shows all categories with totals', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => 'traite',
    ]);
    $recu->depenses()->createMany([
        ['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire'],
        ['libelle' => 'Eau', 'quantite' => 6, 'prix_unitaire' => 5.0, 'categorie' => 'boissons'],
    ]);

    $response = $this->actingAs($user)->get(route('depenses.categories'));

    $response->assertStatus(200);
    $response->assertSee('Alimentaire');
    $response->assertSee('Boissons');
    $response->assertSee('Hygiène');
    $response->assertSee('Entretien');
    $response->assertSee('Autre');
});

test('category overview shows zero for categories with no expenses', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('depenses.categories'));

    $response->assertStatus(200);
    $response->assertSee('Alimentaire');
    $response->assertSee('0.00 MAD');
});

test('category overview data is scoped to authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $recu = $user1->recus()->create(['texte_brut' => 'Facture user1 longue', 'statut' => 'traite']);
    $recu->depenses()->create(['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire']);

    $response = $this->actingAs($user2)->get(route('depenses.categories'));

    $response->assertStatus(200);
    $response->assertSee('Alimentaire');
    $response->assertDontSee('3,00 MAD');
});

test('expense index page shows category summary sidebar', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => 'traite',
    ]);
    $recu->depenses()->createMany([
        ['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire'],
        ['libelle' => 'Eau', 'quantite' => 6, 'prix_unitaire' => 5.0, 'categorie' => 'boissons'],
    ]);

    $response = $this->actingAs($user)->get(route('depenses.index'));

    $response->assertStatus(200);
    $response->assertSee('Résumé par catégorie');
    $response->assertSee('Alimentaire');
    $response->assertSee('Boissons');
    $response->assertSee('Voir l\'aperçu complet');
});

test('category summary highlights selected category', function () {
    $user = User::factory()->create();
    $recu = $user->recus()->create([
        'texte_brut' => 'Facture test avec du texte suffisamment long',
        'statut' => 'traite',
    ]);
    $recu->depenses()->createMany([
        ['libelle' => 'Pain', 'quantite' => 2, 'prix_unitaire' => 1.5, 'categorie' => 'alimentaire'],
        ['libelle' => 'Eau', 'quantite' => 6, 'prix_unitaire' => 5.0, 'categorie' => 'boissons'],
    ]);

    $response = $this->actingAs($user)->get(route('depenses.index', ['categorie' => 'alimentaire']));

    $response->assertStatus(200);
    $response->assertSee('Résumé par catégorie');
});
