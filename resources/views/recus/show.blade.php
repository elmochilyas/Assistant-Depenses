<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Détail du reçu
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Détail du reçu</h1>
            <a href="{{ route('recus.index') }}" class="text-blue-600 hover:underline">Retour à la liste</a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Informations du reçu</h2>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                    @if($recu->statut->value === 'en_attente') bg-yellow-100 text-yellow-800
                    @elseif($recu->statut->value === 'traite') bg-green-100 text-green-800
                    @elseif($recu->statut->value === 'echoue') bg-red-100 text-red-800
                    @endif
                ">
                    {{ $recu->statut->label() }}
                </span>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">Texte brut</label>
                <div class="bg-gray-50 p-4 rounded-lg font-mono text-sm whitespace-pre-wrap max-h-96 overflow-auto">{{ $recu->texte_brut }}</div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Créé le {{ $recu->created_at->format('d/m/Y à H:i') }}</span>
                <form action="{{ route('recus.destroy', $recu) }}" method="POST" onsubmit="return confirm('Supprimer ce reçu et toutes ses dépenses ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Supprimer</button>
                </form>
            </div>
        </div>

        @if ($recu->statut->value === 'echoue' && $recu->message_erreur)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h3 class="font-medium text-red-800 mb-2">Erreur d'extraction</h3>
                <p class="text-red-700">{{ $recu->message_erreur }}</p>
            </div>
        @endif

        @if ($recu->depenses->isNotEmpty())
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <h2 class="text-lg font-semibold text-gray-900 p-6 border-b border-gray-200">Dépenses extraites ({{ $recu->depenses->count() }})</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Libellé</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qté</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Prix unitaire</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($recu->depenses as $depense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $depense->libelle }}</td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900">{{ $depense->quantite }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900">{{ number_format($depense->prix_unitaire, 2, ',', ' ') }} MAD</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $depense->categorie->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($recu->statut->value === 'traite')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-800">Aucune dépense extraite pour ce reçu.</p>
            </div>
        @endif
    </div>
</x-app-layout>
