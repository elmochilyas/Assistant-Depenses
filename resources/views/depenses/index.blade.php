<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mes dépenses') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="GET" action="{{ route('depenses.index') }}">
                        <div class="flex items-center gap-4">
                            <x-input-label for="categorie" :value="__('Catégorie')" />
                            <select id="categorie" name="categorie" onchange="this.form.submit()" class="rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600">
                                <option value="">{{ __('Toutes les catégories') }}</option>
                                @foreach (App\Enums\CategorieDepense::cases() as $cat)
                                    <option value="{{ $cat->value }}" @selected(request('categorie') === $cat->value)>
                                        {{ $cat->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @if (request('categorie'))
                                <a href="{{ route('depenses.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Effacer le filtre') }}</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4 text-gray-900 dark:text-gray-100">
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ __('Résumé par catégorie') }}</h3>
                            <ul class="space-y-2">
                                @foreach ($categorySummary as $cat)
                                    <li>
                                        <a href="{{ route('depenses.index', ['categorie' => $cat->categorie->value]) }}"
                                           class="flex items-center justify-between text-sm px-3 py-2 rounded-md transition
                                                  {{ ($selectedCategorie ?? '') === $cat->categorie->value ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300' }}">
                                            <span>{{ $cat->categorie->label() }}</span>
                                            <span class="text-xs">{{ $cat->total_count }} · {{ number_format($cat->total_amount, 0) }} MAD</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <a href="{{ route('depenses.categories') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ __('Voir l\'aperçu complet &rarr;') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            @if ($depenses->isEmpty())
                                <p>{{ __('Aucune dépense trouvée.') }}</p>
                            @else
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Libellé') }}</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Quantité') }}</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Prix unitaire') }}</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Catégorie') }}</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Reçu') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($depenses as $depense)
                                            <tr>
                                                <td class="px-4 py-2 text-sm">{{ $depense->libelle }}</td>
                                                <td class="px-4 py-2 text-sm">{{ $depense->quantite }}</td>
                                                <td class="px-4 py-2 text-sm">{{ number_format($depense->prix_unitaire, 2) }} {{ $depense->recu->payload_ia['devise'] ?? 'MAD' }}</td>
                                                <td class="px-4 py-2 text-sm">{{ $depense->categorie->label() }}</td>
                                                <td class="px-4 py-2 text-sm">
                                                    <a href="{{ route('recus.show', $depense->recu) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                        {{ Str::limit($depense->recu->texte_brut, 40) }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
