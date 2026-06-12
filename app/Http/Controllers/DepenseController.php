<?php

namespace App\Http\Controllers;

use App\Enums\CategorieDepense;
use App\Models\Depense;
use Illuminate\Support\Facades\DB;

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

        $categorySummary = $this->buildCategorySummary();

        return view('depenses.index', [
            'depenses' => $depenses->latest()->get(),
            'selectedCategorie' => $categorie,
            'categorySummary' => $categorySummary,
        ]);
    }

    public function categories()
    {
        $categories = $this->buildCategorySummary();

        return view('depenses.categories', [
            'categories' => $categories,
        ]);
    }

    private function buildCategorySummary(): array
    {
        $userId = auth()->id();

        $aggregations = Depense::whereHas('recu', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->select('categorie', DB::raw('COUNT(*) as total_count'), DB::raw('SUM(prix_unitaire * quantite) as total_amount'))
            ->groupBy('categorie')
            ->get()
            ->keyBy('categorie');

        $categories = [];

        foreach (CategorieDepense::cases() as $cat) {
            $agg = $aggregations->get($cat->value);
            $categories[] = (object) [
                'categorie' => $cat,
                'total_count' => $agg->total_count ?? 0,
                'total_amount' => (float) ($agg->total_amount ?? 0),
            ];
        }

        return $categories;
    }
}
