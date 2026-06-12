<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\StructuredAnonymousAgent;

class ExtracteurDepenses extends StructuredAnonymousAgent implements HasStructuredOutput
{
    public function __construct()
    {
        parent::__construct(
            instructions: 'Tu es un assistant spécialisé dans l\'extraction de dépenses à partir de reçus fournisseurs. Extrais chaque article acheté, normalise le libellé, déduis la catégorie parmi les valeurs autorisées uniquement, préserve les quantités en entiers et les prix unitaires en nombres. Retourne le total estimé et la devise.',
            messages: [],
            tools: [],
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'articles' => $schema->array()->items(
                $schema->object([
                    'libelle' => $schema->string()->required(),
                    'quantite' => $schema->integer()->required(),
                    'prix_unitaire' => $schema->number()->required(),
                    'categorie' => $schema->string()->required()->enum([
                        'alimentaire', 'boissons', 'hygiene', 'entretien', 'autre',
                    ]),
                ])
            )->required(),
            'total_estime' => $schema->number()->required(),
            'devise' => $schema->string()->required(),
        ];
    }
}
