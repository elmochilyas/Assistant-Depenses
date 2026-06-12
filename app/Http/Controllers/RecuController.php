<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecuRequest;
use App\Jobs\ExtraireDepensesDuRecu;
use App\Models\Recu;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecuController extends Controller
{
    public function index(): View
    {
        $recus = auth()->user()
            ->recus()
            ->withCount('depenses')
            ->latest()
            ->paginate(15);

        return view('recus.index', compact('recus'));
    }

    public function create(): View
    {
        return view('recus.create');
    }

    public function store(StoreRecuRequest $request): RedirectResponse
    {
        $recu = auth()->user()->recus()->create([
            'texte_brut' => $request->validated('texte_brut'),
            'statut' => 'en_attente',
        ]);

        ExtraireDepensesDuRecu::dispatch($recu);

        return redirect()
            ->route('recus.index')
            ->with('success', 'Reçu en cours de traitement');
    }

    public function show(Recu $recu): View
    {
        $this->authorize('view', $recu);

        $recu->load('depenses');

        return view('recus.show', compact('recu'));
    }

    public function destroy(Recu $recu): RedirectResponse
    {
        $this->authorize('delete', $recu);

        $recu->delete();

        return redirect()
            ->route('recus.index')
            ->with('success', 'Reçu supprimé avec succès.');
    }
}