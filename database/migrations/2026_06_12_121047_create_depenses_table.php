<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recu_id')->constrained('recus')->cascadeOnDelete();
            $table->string('libelle');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 10, 2);
            $table->string('categorie');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};