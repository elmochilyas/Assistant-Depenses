<?php

namespace App\Http\Controllers;

use App\Enums\CategorieDepense;
use App\Models\Depense;

class DepenseController extends Controller
{
    public function index()
    {
        $categorie = request()->query('categorie');

        $depenses = Depense::whereHas('recu', function ($query) {
            $query->where('user_id', auth()->id());
        })->with('recu');

        if ($categorie && CategorieDepense::tryFrom($categorie)) {
            $depenses->where('categorie', $categorie);
        }

        return view('depenses.index', [
            'depenses' => $depenses->latest()->get(),
            'selectedCategorie' => $categorie,
        ]);
    }
}
