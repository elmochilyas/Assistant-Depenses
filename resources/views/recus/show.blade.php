<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Détail du reçu') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('recus.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">&larr; {{ __('Retour à la liste') }}</a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <span class="font-medium">{{ __('Statut') }} :</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full @if($recu->statut === 'en_attente') bg-yellow-100 text-yellow-800 @elseif($recu->statut === 'traite') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                            {{ $recu->statut->label() }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <span class="font-medium">{{ __('Date') }} :</span>
                        <span class="ml-2">{{ $recu->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div>
                        <span class="font-medium">{{ __('Texte source') }} :</span>
                        <pre class="mt-2 p-4 bg-gray-100 dark:bg-gray-900 rounded text-sm whitespace-pre-wrap">{{ $recu->texte_brut }}</pre>
                    </div>
                </div>
            </div>

            @if ($recu->statut === 'echoue' && $recu->message_erreur)
                <div class="mb-6 px-4 py-3 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded">
                    {{ $recu->message_erreur }}
                </div>
            @endif

            @if ($recu->statut === 'traite' && $recu->depenses->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">{{ __('Dépenses extraites') }}</h3>

                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Libellé') }}</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Quantité') }}</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Prix unitaire') }}</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Catégorie') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($recu->depenses as $depense)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $depense->libelle }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $depense->quantite }}</td>
                                        <td class="px-4 py-2 text-sm">{{ number_format($depense->prix_unitaire, 2) }} {{ $recu->payload_ia['devise'] ?? 'MAD' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $depense->categorie->label() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
