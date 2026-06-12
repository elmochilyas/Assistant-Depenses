<?php

namespace Database\Factories;

use App\Models\Recu;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecuFactory extends Factory
{
    protected $model = Recu::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'texte_brut' => $this->faker->realText(200),
            'statut' => 'en_attente',
        ];
    }
}
