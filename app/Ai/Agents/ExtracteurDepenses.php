<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class ExtracteurDepenses implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
Tu es un assistant spécialisé dans l'extraction de données de reçus de fournisseurs marocains.

Analyse le texte brut du reçu fourni par l'utilisateur et extrais chaque article acheté sous forme structurée.

Règles d'extraction :
1. Pour chaque article, extrait : libellé (nom du produit), quantité (entier), prix unitaire (nombre), catégorie
2. Les catégories autorisées sont UNIQUEMENT : alimentaire, boissons, hygiene, entretien, autre
3. Infère la catégorie depuis le nom du produit (ex: "lait" -> alimentaire, "savon" -> hygiene, "lessive" -> entretien)
4. Normalise le libellé (retire les codes-barres, abréviations inutiles)
5. Calcule ou extrait le total estimé et la devise (généralement MAD au Maroc)
6. Retourne UNIQUEMENT la structure JSON demandée, sans commentaire ni texte additionnel
PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'articles' => $schema->array()
                ->items($schema->object(function (JsonSchema $schema) {
                    return [
                        'libelle' => $schema->string(),
                        'quantite' => $schema->integer(),
                        'prix_unitaire' => $schema->number(),
                        'categorie' => $schema->string()->enum(['alimentaire', 'boissons', 'hygiene', 'entretien', 'autre']),
                    ];
                }))
                ->required(),
            'total_estime' => $schema->number()->required(),
            'devise' => $schema->string()->required(),
        ];
    }
}
