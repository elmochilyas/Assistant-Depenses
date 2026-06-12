<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mes reçus
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Mes reçus</h1>
            <a href="{{ route('recus.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                Nouveau reçu
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @if ($recus->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500 mb-4">Aucun reçu pour le moment.</p>
                <a href="{{ route('recus.create') }}" class="text-blue-600 hover:underline">Créer votre premier reçu</a>
            </div>
        @else
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aperçu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dépenses</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($recus as $recu)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="max-w-xs truncate text-sm text-gray-900">{{ Str::limit($recu->texte_brut, 60) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($recu->statut->value === 'en_attente') bg-yellow-100 text-yellow-800
                                        @elseif($recu->statut->value === 'traite') bg-green-100 text-green-800
                                        @elseif($recu->statut->value === 'echoue') bg-red-100 text-red-800
                                        @endif
                                    ">
                                        {{ $recu->statut->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $recu->depenses_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $recu->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('recus.show', $recu) }}" class="text-blue-600 hover:text-blue-900 text-sm">Voir</a>
                                        <form action="{{ route('recus.destroy', $recu) }}" method="POST" onsubmit="return confirm('Supprimer ce reçu et ses dépenses ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $recus->links() }}
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
