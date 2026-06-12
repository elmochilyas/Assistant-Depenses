<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecuRequest;
use App\Jobs\ExtraireDepensesDuRecu;
use App\Models\Recu;

class RecuController extends Controller
{
    public function index()
    {
        $recus = auth()->user()->recus()->withCount('depenses')->latest()->get();

        return view('recus.index', compact('recus'));
    }

    public function create()
    {
        return view('recus.create');
    }

    public function store(StoreRecuRequest $request)
    {
        $recu = auth()->user()->recus()->create([
            'texte_brut' => $request->texte_brut,
            'statut' => 'en_attente',
        ]);

        ExtraireDepensesDuRecu::dispatch($recu);

        return redirect()->route('recus.index')
            ->with('success', 'Reçu en cours de traitement.');
    }

    public function show(Recu $recu)
    {
        $this->authorize('view', $recu);

        $recu->load('depenses');

        return view('recus.show', compact('recu'));
    }

    public function destroy(Recu $recu)
    {
        $this->authorize('delete', $recu);

        $recu->delete();

        return redirect()->route('recus.index')
            ->with('success', 'Reçu supprimé.');
    }
}
